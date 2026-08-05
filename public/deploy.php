<?php

/**
 * RAMS — GitHub Webhook Auto-Deployment Handler
 *
 * Triggered by GitHub on every push to the main branch.
 * Validates HMAC SHA256 signature before executing any command.
 *
 * Required in production .env:
 *   DEPLOY_WEBHOOK_SECRET=<your-generated-secret>
 *
 * Optional in production .env (override auto-detected paths):
 *   DEPLOY_PHP_BINARY=/usr/bin/php84
 *   DEPLOY_COMPOSER_BINARY=/usr/local/bin/composer
 *   DEPLOY_GIT_BINARY=/usr/bin/git
 *
 * Webhook URL: https://rams.babiesworld.com.pk/deploy.php
 * Log file:    storage/logs/deploy.log
 */

declare(strict_types=1);

// Never expose PHP errors to the HTTP response
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Increase execution time limit for deployment steps
set_time_limit(300);

// ─── Constants ───────────────────────────────────────────────────────────────

define('PROJECT_ROOT', dirname(__DIR__));
define('ARTISAN_PATH', PROJECT_ROOT.'/artisan');
define('DEPLOY_LOG', PROJECT_ROOT.'/storage/logs/deploy.log');
define('DEPLOY_LOCK', PROJECT_ROOT.'/storage/framework/deploy.lock');
define('DEPLOY_BRANCH', 'main');

// ─── Bootstrap ───────────────────────────────────────────────────────────────

/**
 * Write a timestamped line to the deployment log.
 * Never write user-supplied data into the log without sanitising.
 */
function deployLog(string $message): void
{
    $timestamp = date('Y-m-d H:i:s T');
    @file_put_contents(DEPLOY_LOG, "[{$timestamp}] {$message}".PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Send a JSON response and terminate.
 * Never include raw command output in the response body.
 *
 * @param  array<string, mixed>  $data
 */
function deployRespond(int $statusCode, array $data): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('X-Robots-Tag: noindex, nofollow');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Finish the webhook response before running the lengthy deployment process.
 *
 * @param  array<string, mixed>  $data
 */
function deployAcknowledge(array $data): void
{
    http_response_code(202);
    header('Content-Type: application/json');
    header('X-Robots-Tag: noindex, nofollow');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    fastcgi_finish_request();
}

/**
 * Parse the production .env file and return key→value pairs.
 * Only reads—never writes—the .env file.
 *
 * @return array<string, string>
 */
function parseEnvFile(): array
{
    $env = [];
    $path = PROJECT_ROOT.'/.env';

    if (! file_exists($path) || ! is_readable($path)) {
        return $env;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $env;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and lines without an assignment
        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Strip surrounding quotes from value
        $value = trim(trim($value), '"\'');

        if ($key !== '') {
            $env[$key] = $value;
        }
    }

    return $env;
}

/**
 * Locate an executable binary.
 * Checks the .env override first, then common cPanel/VPS paths.
 *
 * @param  array<string, string>  $env
 */
function findBinary(string $name, string $envKey, array $env): string
{
    // 1. Explicit override from .env
    if (! empty($env[$envKey]) && is_executable($env[$envKey])) {
        return $env[$envKey];
    }

    // 2. Common cPanel / VPS paths (ordered by priority)
    $candidates = match ($name) {
        'php' => [
            '/usr/bin/php84',
            '/usr/bin/php83',
            '/usr/bin/php82',
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/opt/php84/bin/php',
            '/opt/php83/bin/php',
        ],
        'composer' => [
            '/usr/local/bin/composer',
            '/usr/local/cpanel/3rdparty/bin/composer',
            '/opt/cpanel/composer/bin/composer',
            '/usr/bin/composer',
        ],
        'git' => [
            '/usr/bin/git',
            '/usr/local/bin/git',
        ],
        default => [],
    };

    foreach ($candidates as $path) {
        if (is_executable($path)) {
            return $path;
        }
    }

    // 3. Fallback: rely on PATH
    return $name;
}

/**
 * Execute a shell command and capture output + exit code.
 * Runs in the project root directory.
 *
 * @return array{exit_code: int, output: string}
 */
function runCommand(string $command): array
{
    $output = [];
    $exitCode = 0;

    // Use exec; proc_open is not available on all shared hosts
    exec($command.' 2>&1', $output, $exitCode);

    return [
        'exit_code' => $exitCode,
        'output' => implode("\n", $output),
    ];
}

// ─── Request Validation ──────────────────────────────────────────────────────

// Only POST requests are accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deployLog('REJECTED: Non-POST request ('.$_SERVER['REQUEST_METHOD'].')');
    deployRespond(405, ['success' => false, 'error' => 'Method Not Allowed']);
}

// Read raw payload before any other processing
$rawPayload = (string) file_get_contents('php://input');

if ($rawPayload === '') {
    deployLog('REJECTED: Empty request body');
    deployRespond(400, ['success' => false, 'error' => 'Empty payload']);
}

// Load configuration from .env
$env = parseEnvFile();
$webhookSecret = $env['DEPLOY_WEBHOOK_SECRET'] ?? '';

if ($webhookSecret === '') {
    deployLog('ERROR: DEPLOY_WEBHOOK_SECRET is not set in .env');
    deployRespond(500, ['success' => false, 'error' => 'Deployment not configured on this server']);
}

// Validate HMAC SHA256 signature (constant-time comparison — safe against timing attacks)
$signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($signatureHeader === '') {
    deployLog('REJECTED: Missing X-Hub-Signature-256 header — IP: '.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    deployRespond(403, ['success' => false, 'error' => 'Forbidden']);
}

$expectedSignature = 'sha256='.hash_hmac('sha256', $rawPayload, $webhookSecret);

if (! hash_equals($expectedSignature, $signatureHeader)) {
    deployLog('REJECTED: Invalid webhook signature — IP: '.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    deployRespond(403, ['success' => false, 'error' => 'Forbidden']);
}

// Validate JSON payload
$payload = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    deployLog('REJECTED: Malformed JSON payload');
    deployRespond(400, ['success' => false, 'error' => 'Invalid payload format']);
}

// Only deploy from the main branch
$ref = (string) ($payload['ref'] ?? '');
$pushedBranch = str_replace('refs/heads/', '', $ref);

if ($pushedBranch !== DEPLOY_BRANCH) {
    deployLog("SKIPPED: Push to branch '{$pushedBranch}' — only '".DEPLOY_BRANCH."' triggers deployment");
    deployRespond(200, [
        'success' => true,
        'message' => "Branch '{$pushedBranch}' does not trigger deployment.",
    ]);
}

// ─── Deployment ──────────────────────────────────────────────────────────────

if (! function_exists('fastcgi_finish_request')) {
    deployLog('ERROR: PHP-FPM fastcgi_finish_request() is required for deployment');
    deployRespond(503, ['success' => false, 'error' => 'Deployment requires PHP-FPM']);
}

// Serialize authenticated deployments so duplicate webhook deliveries cannot
// interleave repository, dependency, migration, or maintenance-mode changes.
$deployLock = @fopen(DEPLOY_LOCK, 'c');

if ($deployLock === false) {
    deployLog('ERROR: Could not open deployment lock');
    deployRespond(500, ['success' => false, 'error' => 'Unable to initialize deployment']);
}

if (! flock($deployLock, LOCK_EX | LOCK_NB)) {
    fclose($deployLock);
    deployLog('SKIPPED: Deployment already in progress');
    deployRespond(409, ['success' => false, 'error' => 'Deployment already in progress']);
}

register_shutdown_function(static function () use ($deployLock): void {
    flock($deployLock, LOCK_UN);
    fclose($deployLock);
});

ignore_user_abort(true);
deployAcknowledge([
    'success' => true,
    'status' => 'accepted',
    'message' => 'Deployment accepted. Check storage/logs/deploy.log for outcome.',
]);

// Safe metadata for logging (never user-controlled paths or shell-injectable values)
$commitShort = substr(preg_replace('/[^a-f0-9]/', '', (string) ($payload['after'] ?? '')), 0, 8);
$committer = preg_replace('/[^a-zA-Z0-9 _\-@.]/', '', (string) ($payload['head_commit']['committer']['name'] ?? 'unknown'));
$commitMessage = substr(preg_replace('/[^\w\s\-.,!?:()\[\]]/', '', (string) ($payload['head_commit']['message'] ?? '')), 0, 80);

// Record the pre-deployment commit so operations team can rollback if needed
$previousCommit = trim((string) shell_exec(escapeshellcmd('git').' -C '.escapeshellarg(PROJECT_ROOT).' rev-parse HEAD 2>/dev/null'));
$previousCommit = preg_replace('/[^a-f0-9]/', '', $previousCommit);

// Locate runtime binaries
$phpBin = findBinary('php', 'DEPLOY_PHP_BINARY', $env);
$composerBin = findBinary('composer', 'DEPLOY_COMPOSER_BINARY', $env);
$gitBin = findBinary('git', 'DEPLOY_GIT_BINARY', $env);

// Unique identifier for this deployment run
$deployId = date('YmdHis').'_'.$commitShort;
$startTime = microtime(true);

deployLog('======================================================');
deployLog("DEPLOY START [{$deployId}]");
deployLog('Branch  : '.DEPLOY_BRANCH);
deployLog("Commit  : {$commitShort}");
deployLog("Author  : {$committer}");
deployLog("Message : {$commitMessage}");
deployLog("Previous: {$previousCommit}");
deployLog("PHP     : {$phpBin}");
deployLog("Composer: {$composerBin}");
deployLog("Git     : {$gitBin}");
deployLog('======================================================');

/** @var array<string, array{status: string, exit_code: int}> $steps */
$steps = [];
$failed = false;
$failedStep = '';

/**
 * Execute one deployment step.
 * If a previous step has already failed, this step is skipped.
 */
$runStep = function (string $stepName, string $command) use (&$steps, &$failed, &$failedStep): void {
    if ($failed) {
        deployLog("STEP [{$stepName}]: SKIPPED (earlier failure)");
        $steps[$stepName] = ['status' => 'skipped', 'exit_code' => -1];

        return;
    }

    deployLog("STEP [{$stepName}]: running");
    $result = runCommand($command);
    $status = $result['exit_code'] === 0 ? 'ok' : 'failed';

    // Capture output to log only — never echo to response
    if ($result['output'] !== '') {
        deployLog("STEP [{$stepName}]: output captured ({$result['exit_code']})");
    }

    deployLog("STEP [{$stepName}]: {$status}");

    if ($result['exit_code'] !== 0) {
        $failed = true;
        $failedStep = $stepName;
    }

    $steps[$stepName] = ['status' => $status, 'exit_code' => $result['exit_code']];
};

// ── Step 1: Enable maintenance mode ─────────────────────────────────────────
$runStep('maintenance:on', $phpBin.' '.escapeshellarg(ARTISAN_PATH).' down --no-interaction');

// ── Step 2: Fetch latest code from origin ───────────────────────────────────
$runStep('git:fetch', $gitBin.' -C '.escapeshellarg(PROJECT_ROOT).' fetch --all --prune');

// ── Step 3: Hard reset to origin/main ───────────────────────────────────────
$runStep('git:reset', $gitBin.' -C '.escapeshellarg(PROJECT_ROOT).' reset --hard origin/'.DEPLOY_BRANCH);

// ── Step 4: Install production Composer dependencies ─────────────────────────
$runStep(
    'composer:install',
    $composerBin.' install'
    .' --no-dev'
    .' --optimize-autoloader'
    .' --no-interaction'
    .' --prefer-dist'
    .' --working-dir='.escapeshellarg(PROJECT_ROOT)
);

// ── Step 5: Run database migrations ─────────────────────────────────────────
$runStep('migrate', $phpBin.' '.escapeshellarg(ARTISAN_PATH).' migrate --force --no-interaction');

// ── Step 6: Clear all application caches ────────────────────────────────────
$runStep('optimize:clear', $phpBin.' '.escapeshellarg(ARTISAN_PATH).' optimize:clear --no-interaction');

// ── Step 7: Re-warm all application caches ──────────────────────────────────
$runStep('optimize', $phpBin.' '.escapeshellarg(ARTISAN_PATH).' optimize --no-interaction');

// ── Step 8: Terminate Horizon (Supervisor will restart it) ──────────────────
$runStep('horizon:terminate', $phpBin.' '.escapeshellarg(ARTISAN_PATH).' horizon:terminate --no-interaction');

// ── Step 9: Disable maintenance mode ────────────────────────────────────────
// Always attempt this — even when a prior step failed
$upResult = runCommand($phpBin.' '.escapeshellarg(ARTISAN_PATH).' up --no-interaction');
$upStatus = $upResult['exit_code'] === 0 ? 'ok' : 'failed';
deployLog('STEP [maintenance:off]: '.$upStatus);
$steps['maintenance:off'] = ['status' => $upStatus, 'exit_code' => $upResult['exit_code']];

// ─── Result ──────────────────────────────────────────────────────────────────

$duration = round(microtime(true) - $startTime, 2);

if ($failed) {
    deployLog("DEPLOY FAILED [{$deployId}] — failed at step: {$failedStep} — duration: {$duration}s");
    deployLog("ROLLBACK HINT: git reset --hard {$previousCommit}");
    deployLog('======================================================');

    exit;
}

deployLog("DEPLOY SUCCESS [{$deployId}] — all steps passed — duration: {$duration}s");
deployLog("Rollback ref: {$previousCommit}");
deployLog('======================================================');

exit;
