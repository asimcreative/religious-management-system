<?php

/**
 * RAMS — Manual Git Pull Trigger
 *
 * Secure URL-based deployment trigger. Hit this URL with the correct
 * token and it will pull latest code and run post-deploy commands.
 *
 * Usage:
 *   GET  https://rams.babiesworld.com.pk/pull.php?token=YOUR_SECRET
 *   POST https://rams.babiesworld.com.pk/pull.php  (body: token=YOUR_SECRET)
 *
 * Required in production .env:
 *   DEPLOY_PULL_TOKEN=<your-generated-secret>
 *
 * Optional in production .env (override auto-detected paths):
 *   DEPLOY_PHP_BINARY=/usr/bin/php84
 *   DEPLOY_GIT_BINARY=/usr/bin/git
 *   DEPLOY_COMPOSER_BINARY=/usr/local/bin/composer
 *
 * Log file: storage/logs/deploy.log
 */

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
set_time_limit(300);

// ─── Constants ───────────────────────────────────────────────────────────────

// Locate the Laravel project root. On standard setups this file is inside
// public/ so the root is one level up (dirname(__DIR__)). On shared hosting
// where the entire project is served from public_html/, this file lives in
// the same directory as artisan. Walk upward until we find artisan or .env.
(static function (): void {
    $candidates = [dirname(__DIR__), __DIR__];
    foreach ($candidates as $dir) {
        if (file_exists($dir.'/artisan') || file_exists($dir.'/.env')) {
            define('PROJECT_ROOT', $dir);

            return;
        }
    }
    define('PROJECT_ROOT', dirname(__DIR__));
})();

define('ARTISAN_PATH', PROJECT_ROOT.'/artisan');
define('DEPLOY_LOG', PROJECT_ROOT.'/storage/logs/deploy.log');
define('DEPLOY_LOCK', PROJECT_ROOT.'/storage/framework/deploy.lock');
define('DEPLOY_BRANCH', 'main');

// ─── Helpers ─────────────────────────────────────────────────────────────────

function pullLog(string $message): void
{
    $timestamp = date('Y-m-d H:i:s T');
    @file_put_contents(DEPLOY_LOG, "[{$timestamp}] {$message}".PHP_EOL, FILE_APPEND | LOCK_EX);
}

function pullRespond(int $status, string $message, array $steps = []): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('X-Robots-Tag: noindex, nofollow');
    $payload = ['success' => $status < 400, 'message' => $message];
    if ($steps !== []) {
        $payload['steps'] = $steps;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * @return array<string, string>
 */
function pullReadEnv(): array
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
        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), '"\'');
        if ($key !== '') {
            $env[$key] = $value;
        }
    }

    return $env;
}

function pullFindBin(string $name, string $envKey, array $env): string
{
    if (! empty($env[$envKey]) && is_executable($env[$envKey])) {
        return $env[$envKey];
    }

    $candidates = match ($name) {
        'php' => [
            '/usr/bin/php84', '/usr/bin/php83', '/usr/bin/php82',
            '/usr/local/bin/php', '/usr/bin/php',
            '/opt/php84/bin/php', '/opt/php83/bin/php',
        ],
        'git' => ['/usr/bin/git', '/usr/local/bin/git'],
        'composer' => [
            '/usr/local/bin/composer',
            '/usr/local/cpanel/3rdparty/bin/composer',
            '/opt/cpanel/composer/bin/composer',
            '/usr/bin/composer',
        ],
        default => [],
    };

    foreach ($candidates as $path) {
        if (is_executable($path)) {
            return $path;
        }
    }

    return $name;
}

function pullRun(string $command): array
{
    $output = [];
    $exitCode = 0;
    exec($command.' 2>&1', $output, $exitCode);

    return ['exit_code' => $exitCode, 'output' => implode("\n", $output)];
}

// ─── Auth ────────────────────────────────────────────────────────────────────

// Accept token from GET param or POST body
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($token === '') {
    pullLog('REJECTED: No token provided — IP: '.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    pullRespond(403, 'Forbidden: token required');
}

$env = pullReadEnv();
$correctToken = $env['DEPLOY_PULL_TOKEN'] ?? '';

if ($correctToken === '') {
    pullLog('ERROR: DEPLOY_PULL_TOKEN is not set in .env');
    pullRespond(500, 'Pull not configured on this server');
}

// Constant-time comparison to prevent timing attacks
if (! hash_equals($correctToken, $token)) {
    pullLog('REJECTED: Invalid token — IP: '.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    pullRespond(403, 'Forbidden: invalid token');
}

// ─── Lock ────────────────────────────────────────────────────────────────────

$lockHandle = @fopen(DEPLOY_LOCK, 'c');

if ($lockHandle === false) {
    pullLog('ERROR: Could not open lock file');
    pullRespond(500, 'Unable to start pull (lock error)');
}

if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fclose($lockHandle);
    pullLog('SKIPPED: Pull already in progress');
    pullRespond(409, 'Pull already in progress — try again in a moment');
}

register_shutdown_function(static function () use ($lockHandle): void {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

// ─── Pull ────────────────────────────────────────────────────────────────────

$phpBin = pullFindBin('php', 'DEPLOY_PHP_BINARY', $env);
$gitBin = pullFindBin('git', 'DEPLOY_GIT_BINARY', $env);
$composerBin = pullFindBin('composer', 'DEPLOY_COMPOSER_BINARY', $env);

$pullId = date('YmdHis');
$startTime = microtime(true);
$steps = [];
$failed = false;

pullLog('======================================================');
pullLog("PULL START [{$pullId}]");
pullLog("PHP: {$phpBin} | Git: {$gitBin}");
pullLog('======================================================');

$step = function (string $name, string $cmd) use (&$steps, &$failed): void {
    if ($failed) {
        pullLog("STEP [{$name}]: SKIPPED");
        $steps[$name] = 'skipped';

        return;
    }

    pullLog("STEP [{$name}]: running");
    $r = pullRun($cmd);
    $status = $r['exit_code'] === 0 ? 'ok' : 'failed';

    if ($r['output'] !== '') {
        // Log output but never expose it in the HTTP response
        foreach (array_slice(explode("\n", $r['output']), 0, 20) as $line) {
            pullLog('  > '.trim($line));
        }
    }

    pullLog("STEP [{$name}]: {$status} (exit {$r['exit_code']})");
    $steps[$name] = $status;

    if ($r['exit_code'] !== 0) {
        $failed = true;
    }
};

// 1. Maintenance on
$step(
    'maintenance:on',
    $phpBin.' '.escapeshellarg(ARTISAN_PATH).' down --no-interaction'
);

// 2. Git pull
$step(
    'git:pull',
    $gitBin.' -C '.escapeshellarg(PROJECT_ROOT)
    .' pull origin '.DEPLOY_BRANCH.' --ff-only'
);

// 3. Composer install (no dev)
$step(
    'composer:install',
    $composerBin.' install --no-dev --optimize-autoloader --no-interaction --prefer-dist'
    .' --working-dir='.escapeshellarg(PROJECT_ROOT)
);

// 4. Migrate
$step(
    'migrate',
    $phpBin.' '.escapeshellarg(ARTISAN_PATH).' migrate --force --no-interaction'
);

// 5. Clear + warm caches
$step(
    'optimize:clear',
    $phpBin.' '.escapeshellarg(ARTISAN_PATH).' optimize:clear --no-interaction'
);

$step(
    'optimize',
    $phpBin.' '.escapeshellarg(ARTISAN_PATH).' optimize --no-interaction'
);

// 6. Restart queue workers
$step(
    'queue:restart',
    $phpBin.' '.escapeshellarg(ARTISAN_PATH).' queue:restart --no-interaction'
);

// 7. Maintenance off — always run regardless of failures
$r = pullRun($phpBin.' '.escapeshellarg(ARTISAN_PATH).' up --no-interaction');
$upOk = $r['exit_code'] === 0;
pullLog('STEP [maintenance:off]: '.($upOk ? 'ok' : 'failed'));
$steps['maintenance:off'] = $upOk ? 'ok' : 'failed';

// ─── Result ──────────────────────────────────────────────────────────────────

$duration = round(microtime(true) - $startTime, 2);

if ($failed) {
    pullLog("PULL FAILED [{$pullId}] — {$duration}s");
    pullLog('======================================================');
    pullRespond(500, "Pull failed after {$duration}s. Check storage/logs/deploy.log for details.", $steps);
}

pullLog("PULL SUCCESS [{$pullId}] — {$duration}s");
pullLog('======================================================');

pullRespond(200, "Pull successful in {$duration}s.", $steps);
