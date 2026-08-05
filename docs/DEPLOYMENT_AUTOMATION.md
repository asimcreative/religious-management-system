# RAMS — Automatic Deployment Guide

## Overview

This webhook workflow is for a traditional server deployment with a writable Git
checkout. It is intentionally excluded from Docker images; use the image-based
release steps in `DEPLOYMENT_GUIDE.md` for Docker deployments.

Every push to the `main` branch on GitHub automatically triggers a full production deployment via a GitHub Webhook.

```
Developer pushes to main
         │
         ▼
  GitHub Webhook (POST)
         │
         ▼
  public/deploy.php
  (HMAC SHA256 verified)
         │
         ▼
  Maintenance Mode ON
         │
         ▼
  git fetch + reset --hard
         │
         ▼
  composer install (no-dev)
         │
         ▼
  php artisan migrate
         │
         ▼
  optimize:clear + optimize
         │
         ▼
  horizon:terminate
         │
         ▼
  Maintenance Mode OFF
         │
         ▼
  JSON response + deploy.log
```

---

## Files

| File | Purpose |
|---|---|
| `public/deploy.php` | GitHub Webhook receiver and deployment orchestrator |
| `scripts/rollback.sh` | Manual rollback script for emergencies |
| `storage/logs/deploy.log` | Deployment log (timestamp, commit, steps, duration) |

---

## Step 1 — Generate the Webhook Secret

Generate a strong random secret. Run this locally:

```bash
openssl rand -hex 32
```

This produces a 64-character hex string like:

```
a3f8c2d1e4b7f9a0c3d2e5f8a1b4c7d0e3f6a9b2c5d8e1f4a7b0c3d6e9f2a5b8
```

**Keep this secret. Never commit it. Never share it.**

---

## Step 2 — Add Secret to Production .env

SSH or FTP into the production server and edit the `.env` file:

```env
# Auto-Deployment
DEPLOY_WEBHOOK_SECRET=your-64-char-secret-here

# Optional: override auto-detected binary paths
# DEPLOY_PHP_BINARY=/usr/bin/php84
# DEPLOY_COMPOSER_BINARY=/usr/local/bin/composer
# DEPLOY_GIT_BINARY=/usr/bin/git
```

**This variable must exist in the production `.env` only. Never in Git.**

---

## Step 3 — Configure GitHub Webhook

1. Open your GitHub repository: `https://github.com/asimcreative/religious-management-system`
2. Go to **Settings → Webhooks → Add webhook**
3. Fill in the form:

| Field | Value |
|---|---|
| **Payload URL** | `https://rams.babiesworld.com.pk/deploy.php` |
| **Content type** | `application/json` |
| **Secret** | The 64-char secret from Step 1 |
| **Which events** | Just the push event |
| **Active** | ✓ Checked |

4. Click **Add webhook**
5. GitHub will send a ping event — it will be ignored (not a push event, no response logged)

---

## Step 4 — Verify Server Requirements

### Required on the production server

| Requirement | Notes |
|---|---|
| PHP 8.2+ FPM | The webhook must run through PHP-FPM so it can acknowledge GitHub before deployment work begins. |
| PHP 8.2+ CLI | The deploy script invokes `php artisan` with this binary. |
| `exec()` enabled | PHP function used to run shell commands |
| `file_get_contents()` enabled | Used to read the request payload |
| `git` binary | Must be in PATH or configured via `DEPLOY_GIT_BINARY` |
| `composer` binary | Must be in PATH or configured via `DEPLOY_COMPOSER_BINARY` |
| Git repository initialised | `git remote -v` should show `origin` pointing to GitHub |
| Write permission on `storage/` | Deploy log is written there |
| Write permission on `bootstrap/cache/` | Laravel caches are written there |

### Verify PHP can call exec()

SSH into the server and run:

```bash
php -r "echo exec('echo ok');"
# Expected output: ok
```

If blank — `exec` is disabled in your PHP configuration. You must enable it in `php.ini` (or ask your host to do so).

### Verify git remote

```bash
git -C /path/to/rams remote -v
# Expected:
# origin  https://github.com/asimcreative/religious-management-system.git (fetch)
# origin  https://github.com/asimcreative/religious-management-system.git (push)
```

If git requires authentication, configure it using a deploy key or credential helper:

```bash
git config credential.helper store
git pull origin main
# enter GitHub username and personal access token once
```

---

## Step 5 — Set File Permissions

The deployment script must be able to write the log file:

```bash
# Allow web server to write to logs
chmod 775 storage/logs/
chown -R www-data:www-data storage/

# Ensure artisan is executable
chmod +x artisan
```

On cPanel, the owner is usually your cPanel username, not `www-data`. Adjust accordingly.

---

## Step 6 — Test the Webhook

### Manual test via curl

Run this from your local machine (replace values):

```bash
# Generate a test payload
PAYLOAD='{"ref":"refs/heads/main","after":"abc12345","head_commit":{"message":"test","committer":{"name":"Asim"}}}'

# Sign it with your secret
SECRET="your-webhook-secret-here"
SIGNATURE="sha256=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.*= //')"

# Send to your webhook endpoint
curl -X POST https://rams.babiesworld.com.pk/deploy.php \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: $SIGNATURE" \
  -d "$PAYLOAD"
```

Expected response:

```json
{
  "success": true,
  "status": "accepted",
  "message": "Deployment accepted. Check storage/logs/deploy.log for outcome."
}
```

### Via GitHub

After configuring the webhook:

1. Push a small change to `main`
2. Go to **Settings → Webhooks → your webhook → Recent Deliveries**
3. Click the delivery to see the response
4. Check `storage/logs/deploy.log` on the server for details

---

## Deployment Log

Every deployment writes to `storage/logs/deploy.log`:

```
[2026-08-04 12:00:01 UTC] ======================================================
[2026-08-04 12:00:01 UTC] DEPLOY START [20260804120001_a3f8c2d1]
[2026-08-04 12:00:01 UTC] Branch  : main
[2026-08-04 12:00:01 UTC] Commit  : a3f8c2d1
[2026-08-04 12:00:01 UTC] Author  : Asim
[2026-08-04 12:00:01 UTC] Message : feat: add new dashboard widget
[2026-08-04 12:00:01 UTC] Previous: 9b1e5f3a
[2026-08-04 12:00:01 UTC] PHP     : /usr/bin/php84
[2026-08-04 12:00:01 UTC] Composer: /usr/local/bin/composer
[2026-08-04 12:00:01 UTC] Git     : /usr/bin/git
[2026-08-04 12:00:01 UTC] ======================================================
[2026-08-04 12:00:01 UTC] STEP [maintenance:on]: running
[2026-08-04 12:00:02 UTC] STEP [maintenance:on]: ok
[2026-08-04 12:00:02 UTC] STEP [git:fetch]: running
[2026-08-04 12:00:05 UTC] STEP [git:fetch]: ok
...
[2026-08-04 12:00:47 UTC] DEPLOY SUCCESS [20260804120001_a3f8c2d1] — all steps passed — duration: 46.2s
[2026-08-04 12:00:47 UTC] Rollback ref: 9b1e5f3a
[2026-08-04 12:00:47 UTC] ======================================================
```

The "Rollback ref" line on every successful deployment gives you the exact commit to rollback to if the new deployment has issues.

---

## Rollback

### When to rollback

- A deployment succeeded but the application has runtime errors
- A deployment failed midway (the script will log the failed step)
- Business data issue caused by a migration

### Rollback procedure

**Step 1:** Find the rollback commit from the deploy log:

```bash
grep "Rollback ref" storage/logs/deploy.log | tail -5
```

Example output:

```
[2026-08-04 12:00:47 UTC] Rollback ref: 9b1e5f3a
```

**Step 2:** Run the rollback script:

```bash
bash scripts/rollback.sh 9b1e5f3a
```

The script will:
1. Ask for confirmation (you must type `ROLLBACK`)
2. Enable maintenance mode
3. `git reset --hard 9b1e5f3a`
4. Re-install Composer dependencies
5. Run `migrate` (if the bad commit added a migration — rollback manually first)
6. Clear and re-warm caches
7. Terminate Horizon
8. Disable maintenance mode

**Step 3:** If the failed deployment added a migration that needs reversing:

```bash
php artisan migrate:rollback --step=1
```

Run this **before** running `scripts/rollback.sh`, or **after step 2** if you added the `--force` flag.

### Emergency manual rollback (SSH)

```bash
# 1. Enable maintenance
php artisan down

# 2. Reset code
git reset --hard <previous-commit>

# 3. Re-install dependencies
composer install --no-dev --optimize-autoloader

# 4. Rollback migration if needed
php artisan migrate:rollback --step=1

# 5. Re-cache
php artisan optimize:clear
php artisan optimize

# 6. Restart queues
php artisan horizon:terminate

# 7. Bring site back up
php artisan up
```

---

## Security Design

### What is protected

| Threat | Protection |
|---|---|
| Unauthenticated requests | HMAC SHA256 signature required |
| Forged payloads | Signature is computed over the raw request body |
| Timing attacks | `hash_equals()` constant-time comparison |
| Non-main branch triggers | Branch check rejects all non-main pushes |
| Delivery timeout | A valid deployment returns `202 Accepted` before the lengthy work begins. |
| Concurrent deliveries | An exclusive deployment lock prevents interleaved deployment steps. |
| Output leakage | Raw command output is never returned; only safe deployment step status is logged. |
| Path traversal | Project root is `dirname(__DIR__)` — not from user input |
| Stack traces | `display_errors=0` and `error_reporting(0)` set at file top |
| Server path exposure | Never included in HTTP responses |
| Environment values | Never included in HTTP responses |

### What the webhook endpoint does NOT expose

- Git output
- Composer output
- Laravel artisan output
- Stack traces
- Server file paths
- Environment variable values
- Database credentials

### What the HTTP response does expose (intentionally)

- `status` — `accepted` after the authenticated deployment has acquired its lock
- `message` — static instruction to consult the deployment log

---

## Troubleshooting

### Webhook returns 403 Forbidden

**Cause:** Signature mismatch.

**Fix:**
- Confirm the secret in GitHub Settings matches `DEPLOY_WEBHOOK_SECRET` in `.env`
- Ensure there are no leading/trailing spaces in `.env`
- Ensure GitHub is sending `X-Hub-Signature-256` (not the old SHA1 header)

### Deployment fails after `202 Accepted`

**Cause:** A deployment step failed after GitHub received the acceptance response.

**Fix:**
1. SSH into the server
2. Check: `tail -100 storage/logs/deploy.log`
3. Look for the `STEP [name]: failed` line
4. Run that step manually to see the full error

### deploy.php returns 500 or 503 before acceptance

**Cause:** `DEPLOY_WEBHOOK_SECRET` is missing from production `.env`, or the endpoint is not running under PHP-FPM.

**Fix:** Add `DEPLOY_WEBHOOK_SECRET=...` to the production `.env` and configure the webhook location to use PHP-FPM.

### git:fetch fails

**Cause:** Git authentication or network issue.

**Fix:**
```bash
git -C /path/to/rams fetch --all
```
If it prompts for a password, set up a credential helper or deploy key.

### composer:install fails

**Cause:** Composer not in PATH or wrong version.

**Fix:**
- Add `DEPLOY_COMPOSER_BINARY=/full/path/to/composer` to `.env`
- Or install Composer: `curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer`

### migrate fails

**Cause:** Database credentials wrong, or migration has an error.

**Fix:**
```bash
php artisan migrate --force
```
Check the full error output.

### Maintenance mode stuck ON after failed deployment

**Cause:** `maintenance:off` step also failed.

**Fix:**
```bash
php artisan up
# or manually delete:
rm storage/framework/down
```

### PHP binary not found

Add to production `.env`:

```env
DEPLOY_PHP_BINARY=/usr/bin/php84
```

Common cPanel PHP paths:

| PHP Version | Path |
|---|---|
| PHP 8.4 | `/usr/bin/php84` |
| PHP 8.3 | `/usr/bin/php83` |
| PHP 8.2 | `/usr/bin/php82` |
| Generic | `/usr/local/bin/php` |

---

## Shared Hosting Notes (cPanel)

On cPanel shared hosting, some PHP functions are often disabled. Verify:

```bash
php -r "phpinfo();" | grep disable_functions
```

The deploy script requires these to be enabled:
- `exec`
- `file_get_contents`
- `file_put_contents`
- `shell_exec` (used for the git rev-parse call only)

If they are disabled, contact your hosting provider and request they be enabled for your account.

### Git setup on cPanel

```bash
# Check git is available
which git
git --version

# Set your identity (required for some git operations)
git config --global user.email "deploy@rams.babiesworld.com.pk"
git config --global user.name "RAMS Deploy"

# Verify remote
git -C /home/youruser/public_html remote -v
```

### Composer setup on cPanel

```bash
# Install Composer in your home directory
cd ~
curl -sS https://getcomposer.org/installer | php
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer

# Add to .env
DEPLOY_COMPOSER_BINARY=/home/youruser/bin/composer
```

---

## Environment Variable Reference

| Variable | Required | Description |
|---|---|---|
| `DEPLOY_WEBHOOK_SECRET` | Yes | GitHub webhook secret. Generate with `openssl rand -hex 32`. Never commit. |
| `DEPLOY_PHP_BINARY` | No | Full path to PHP CLI binary. Auto-detected if not set. |
| `DEPLOY_COMPOSER_BINARY` | No | Full path to Composer binary. Auto-detected if not set. |
| `DEPLOY_GIT_BINARY` | No | Full path to Git binary. Auto-detected if not set. |

---

## Required GitHub Webhook Configuration Summary

| Setting | Value |
|---|---|
| Payload URL | `https://rams.babiesworld.com.pk/deploy.php` |
| Content type | `application/json` |
| Secret | Value of `DEPLOY_WEBHOOK_SECRET` from your production `.env` |
| SSL verification | Enable |
| Events | Just the **push** event |
| Active | Yes |
