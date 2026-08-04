# RAMS — Deployment Guide

## Pre-Deployment Checklist

Before deploying to production:

- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` set and not leaked
- [ ] Database credentials secured
- [ ] Redis password set
- [ ] Mail credentials verified
- [ ] `SANCTUM_TOKEN_EXPIRATION` set (default 43200 = 30 days)
- [ ] `SANCTUM_TOKEN_PREFIX` set to `rams_`
- [ ] Queue connection set to `redis`
- [ ] Cache store set to `redis`
- [ ] Session driver set to `redis`

---

## Deployment Steps

### 1. Pull Latest Code

```bash
git pull origin main
```

### 2. Install Dependencies (No Dev)

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. Run Migrations

```bash
php artisan migrate --force
```

### 4. Clear and Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5. Clear Old Cache

```bash
php artisan cache:clear
```

### 6. Restart Queue Workers

```bash
php artisan horizon:terminate
# Supervisor will restart Horizon automatically
```

### 7. Restart Web Server

```bash
sudo systemctl reload nginx
# or
sudo systemctl reload apache2
```

---

## Rollback

```bash
git checkout <previous-commit>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=1  # only if migration was added
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate
```

---

## Environment Variables Reference

| Variable | Description | Default |
|---|---|---|
| `APP_ENV` | Environment name | `production` |
| `APP_DEBUG` | Show debug info | `false` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `CACHE_STORE` | Cache driver | `redis` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `SANCTUM_TOKEN_EXPIRATION` | API token lifetime (minutes) | `43200` |
| `SANCTUM_TOKEN_PREFIX` | API token prefix | `rams_` |
| `HORIZON_PREFIX` | Horizon Redis key prefix | `rams_horizon:` |

---

## Redis Configuration

For production, use Redis ACLs or a password:

```env
REDIS_PASSWORD=your_redis_password
```

Horizon uses the `redis` connection by default. Configure in `config/horizon.php`.

---

## Horizon Monitoring

Access Horizon dashboard at `/horizon` (Super Admin only in production — set `HORIZON_AUTH_ENABLED=true`).

Metrics snapshot is taken every 5 minutes via the scheduler. To view metrics, start the scheduler:

```bash
php artisan schedule:run  # manually
# or use cron
```

---

## Log Retention

The `logs:purge` command runs daily at 02:00 via the scheduler:

- Activity logs: purged after 730 days (2 years)
- Notifications: purged after 180 days (6 months)
- Audit logs: never purged (7-year retention, archive separately)

To test the purge manually:
```bash
php artisan logs:purge --dry-run
php artisan logs:purge
```

---

## Health Check

Laravel provides a built-in health endpoint at `/up`.

```bash
curl https://your-domain.com/up
# Returns 200 OK if application is healthy
```

---

## SSL / HTTPS

Always use HTTPS in production. If behind a reverse proxy, set:

```env
FORCE_HTTPS=true
```

Or in `AppServiceProvider`:
```php
URL::forceScheme('https');
```

---

## Monitoring Recommendations

- **Horizon**: monitor queue size and failed jobs at `/horizon`
- **Laravel Telescope**: enable on staging for debugging (disable on production)
- **Log files**: `storage/logs/laravel.log` — rotate with `logrotate`
- **Uptime**: monitor `/up` endpoint

---

## Security Hardening

- Run `php artisan about` to verify environment
- Ensure `storage/` and `bootstrap/cache/` are not web-accessible
- Set proper file permissions (755 directories, 644 files)
- Keep `vendor/` out of public directory
- Review and restrict Horizon access to Super Admin role
