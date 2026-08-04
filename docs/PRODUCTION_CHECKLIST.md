# RAMS — Production Deployment Checklist

> Version: v1.0.0 | Date: 2026-08-04 | Status: Release Candidate

Use this checklist for every production deployment. Check each item before going live.

---

## 1. Environment & Configuration

- [ ] `.env` file is present and complete on the server
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` is set (32-character random key, run `php artisan key:generate` once)
- [ ] `APP_URL` is the correct production domain with `https://`
- [ ] `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` all set
- [ ] `SESSION_DRIVER=database`
- [ ] `CACHE_STORE=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` configured
- [ ] `MAIL_*` settings configured for production mailer
- [ ] `SANCTUM_STATEFUL_DOMAINS` includes the production frontend domain
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_SAME_SITE=strict`
- [ ] `HORIZON_ALLOWED_EMAILS` set to authorised email addresses
- [ ] `.env` file is **not** committed to git (confirmed in `.gitignore`)

---

## 2. PHP & Server Requirements

- [ ] PHP 8.3 or higher installed (`php -v`)
- [ ] Required PHP extensions enabled:
  - `pdo_mysql` — database
  - `mbstring` — string handling
  - `openssl` — encryption
  - `tokenizer` — Blade templating
  - `xml` — XML parsing
  - `ctype` — character type
  - `bcmath` — big integer math
  - `fileinfo` — file MIME detection
  - `exif` — image metadata (optional)
  - `gd` or `imagick` — image processing (optional)
  - `zip` — Excel export
  - `redis` PECL extension — Redis connection
- [ ] Composer 2.x installed
- [ ] Node.js 18+ and npm installed (for frontend build)
- [ ] MySQL 8.0+ running
- [ ] Redis 7.0+ running

---

## 3. Web Server (Nginx)

- [ ] Nginx config sets `root` to `/path/to/public`
- [ ] `try_files $uri $uri/ /index.php?$query_string;` is present
- [ ] PHP-FPM socket configured correctly
- [ ] HTTPS (SSL/TLS) certificate installed and auto-renewing (Let's Encrypt)
- [ ] HTTP redirects to HTTPS
- [ ] `server_tokens off;` in Nginx config
- [ ] Gzip compression enabled for text/html, text/css, application/javascript
- [ ] Static asset cache headers set (`expires 1y` for `/build/*`)
- [ ] Upload size in Nginx matches PHP: `client_max_body_size`

---

## 4. Deployment Steps

Run in order on each deployment:

```bash
# 1 — Pull latest code
git pull origin main

# 2 — Install/update PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 3 — Install/build frontend assets
npm ci
npm run build

# 4 — Run new database migrations
php artisan migrate --force

# 5 — Seed required data (idempotent seeders only)
# php artisan db:seed --class=PermissionSeeder --force
# php artisan db:seed --class=RoleSeeder --force

# 6 — Clear and rebuild all caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7 — Restart queue workers
php artisan horizon:terminate
# systemd / supervisor will auto-restart Horizon

# 8 — Restart PHP-FPM
sudo systemctl reload php8.3-fpm

# 9 — Restart Nginx (only if config changed)
sudo nginx -t && sudo systemctl reload nginx

# 10 — Verify health endpoint
curl -s https://yourdomain.com/up
```

---

## 5. Database

- [ ] Migrations have been run (`php artisan migrate --force`)
- [ ] Required seeders have been run:
  - `CompanySeeder` — Creates SYSTEM company
  - `PermissionSeeder` — Seeds 100 permissions
  - `RoleSeeder` — Seeds 20 roles
  - `UserSeeder` — Creates Super Admin user
  - `PrayerSeeder` — Seeds 5 daily prayers
- [ ] Super Admin user password changed from seeder default
- [ ] Database backups scheduled (daily minimum, tested restore)
- [ ] Spatie backup (`spatie/laravel-backup`) configured and tested
- [ ] MySQL slow query log enabled for production monitoring
- [ ] All indexes verified (run `php artisan db:show` or inspect `EXPLAIN` for key queries)

---

## 6. Queue & Horizon

- [ ] Horizon is running (`php artisan horizon`)
- [ ] Horizon managed by `systemd` or `supervisor` for auto-restart
- [ ] `/horizon` dashboard accessible only to Super Admin (gate configured)
- [ ] All 3 queue supervisors active: `high`, `default`, `low`
- [ ] Redis queue connection verified
- [ ] Failed jobs notification email configured
- [ ] `php artisan horizon:snapshot` scheduled in `routes/console.php`
- [ ] Dead letter queue (failed jobs) is being monitored

---

## 7. Scheduler

- [ ] Cron entry present on server:
  ```
  * * * * * cd /var/www/rams && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] `logs:purge` command scheduled (runs daily)
- [ ] `horizon:snapshot` scheduled (runs every 5 minutes)
- [ ] `sanctum:prune-expired` scheduled (runs daily) — removes expired API tokens
- [ ] Schedule log reviewed to confirm jobs are firing

---

## 8. Storage & Files

- [ ] `php artisan storage:link` run (creates `public/storage` symlink)
- [ ] `storage/` directory is writable by web server user
- [ ] `bootstrap/cache/` directory is writable
- [ ] Uploaded files are backed up (if storage is local)
- [ ] Consider S3 or object storage for production file uploads

---

## 9. Security Hardening

- [ ] `APP_DEBUG=false` confirmed
- [ ] No `.env` file accessible via web browser
- [ ] `/horizon` route is not publicly accessible (tested with unauthenticated request)
- [ ] API rate limiting enabled (60 req/min default, see `routes/api.php`)
- [ ] Sanctum token expiry configured (`SANCTUM_EXPIRATION_MINUTES` in sanctum.php)
- [ ] CORS policy configured for API (`config/cors.php`)
- [ ] Security headers present in Nginx:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `X-XSS-Protection: 1; mode=block`
  - `Strict-Transport-Security: max-age=31536000`
  - `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] File upload type/size validation enforced in Form Requests
- [ ] No hardcoded secrets in codebase (run `grep -r "password\|secret\|token" --include="*.php" app/`)

---

## 10. Monitoring & Logging

- [ ] Laravel log channel set to `daily` or `stack` in production
- [ ] Log retention: 30 days minimum (older logs auto-rotated)
- [ ] `logs:purge` retention policy: 730 days activity_log, 180 days notifications
- [ ] Error alerts configured (email or Slack on critical log entries)
- [ ] Uptime monitoring in place (e.g., UptimeRobot, Better Uptime)
- [ ] `/up` health endpoint returning 200 (Laravel default health check)
- [ ] Activity log (Spatie) writing correctly (verified in database)
- [ ] Audit log (RAMS custom) writing correctly

---

## 11. Post-Deployment Smoke Tests

Run manually after every deployment:

- [ ] Login page loads at `https://yourdomain.com/login`
- [ ] Super Admin can log in
- [ ] Dashboard loads with correct stats
- [ ] Employee list loads and pagination works
- [ ] Create a test employee record
- [ ] Create a test teacher record
- [ ] Quran class list loads
- [ ] Salah attendance can be recorded
- [ ] Report generation works (at least one report type)
- [ ] PDF export downloads correctly
- [ ] Excel export downloads correctly
- [ ] Notification can be sent
- [ ] API `/api/v1/login` returns token
- [ ] API `/api/v1/employees` returns data with valid token
- [ ] Horizon dashboard loads at `/horizon`
- [ ] Activity log records are being created
- [ ] Language switcher changes UI to Urdu and back

---

## 12. Rollback Plan

If a deployment fails:

```bash
# 1 — Revert to previous code
git checkout <previous-tag-or-commit>

# 2 — Reinstall dependencies for that version
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

# 3 — Rollback migrations if any were run
php artisan migrate:rollback --step=1

# 4 — Rebuild cache
php artisan optimize

# 5 — Restart services
php artisan horizon:terminate
sudo systemctl reload php8.3-fpm
```

---

## 13. First-Time Production Setup Only

These steps run once when setting up a new production server:

```bash
# Generate application key
php artisan key:generate

# Run full migration and seed
php artisan migrate --force
php artisan db:seed --force

# Create storage symlink
php artisan storage:link

# Install Horizon assets
php artisan horizon:install

# Publish Spatie Permission migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

---

*Checklist must be reviewed and signed off by the lead developer before each production release.*
*Last reviewed: 2026-08-04 | RAMS v1.0.0*
