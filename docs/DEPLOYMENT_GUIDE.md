# RAMS — Deployment Guide

---

## Docker Deployment (Recommended)

The fastest way to run the full stack locally or on a server.

### Requirements

- Docker 24+ and Docker Compose v2
- No local PHP, Node, or MySQL needed — everything runs inside containers

### Files

| File | Purpose |
|---|---|
| `Dockerfile` | Multi-stage build: Node (Vite assets) → Composer (PHP deps) → PHP 8.4-FPM |
| `docker-compose.yml` | Orchestrates all 6 services |
| `nginx.conf` | Nginx config proxying to PHP-FPM |
| `.dockerignore` | Keeps build context lean |
| `docker/entrypoint.sh` | Runs migrations, caching, and volume init on startup |
| `docker/php.ini` | OPcache, memory limits, upload limits |
| `.env.docker` | Docker-ready environment template |

### Services

| Container | Image | Role | Port |
|---|---|---|---|
| `rams_app` | Built from `Dockerfile` | PHP 8.4-FPM (Laravel) | 9000 (internal) |
| `rams_nginx` | `nginx:1.27-alpine` | Web server | `80` → host |
| `rams_mysql` | `mysql:8.0` | Database | `3306` → host |
| `rams_redis` | `redis:7-alpine` | Cache / Sessions / Queues | `6379` → host |
| `rams_horizon` | Same as app | Laravel Horizon (queue manager) | — |
| `rams_scheduler` | Same as app | Laravel task scheduler | — |

### Quick Start

```bash
# 1. Copy Docker environment template
cp .env.docker .env

# 2. Generate APP_KEY
docker compose run --rm app php artisan key:generate --show
# Paste the output as APP_KEY= in .env

# 3. Build and start all services (first run takes a few minutes to build)
docker compose up -d

# 4. Follow startup logs
docker compose logs -f app

# 5. Open in browser
# http://localhost
```

On first start, the `app` container automatically:
- Populates the shared public volume (Vite-compiled assets)
- Runs `php artisan package:discover`
- Runs `php artisan optimize` (config / route / view / event cache)
- Runs `php artisan migrate --force`
- Creates the storage symlink

### Common Commands

```bash
# View all container statuses
docker compose ps

# Tail logs for a specific service
docker compose logs -f horizon
docker compose logs -f scheduler

# Run artisan commands
docker compose exec app php artisan tinker
docker compose exec app php artisan make:model Foo

# Run tests
docker compose exec app php artisan test

# Open a shell in the app container
docker compose exec app bash

# Rebuild after code or Dockerfile changes
docker compose build --no-cache
docker compose up -d

# Stop all containers (preserves volumes / data)
docker compose stop

# Stop and remove containers + volumes (DESTRUCTIVE — wipes DB)
docker compose down -v
```

### Horizon Dashboard

Visit `/horizon` in your browser. Only accessible to users with the `Super Admin` role in production.

### Updating / Redeploying

```bash
git pull origin main
docker compose build
docker compose up -d
```

The entrypoint runs `php artisan migrate --force` on every container start, so migrations are applied automatically.

### Production Notes

1. **APP_KEY** — must be set and kept secret. Never commit it.
2. **DB_PASSWORD / MYSQL_ROOT_PASSWORD** — use strong passwords in production.
3. **REDIS_PASSWORD** — to enable: add `--requirepass yourpassword` to the Redis `command:` in `docker-compose.yml` and set `REDIS_PASSWORD=yourpassword` in `.env`.
4. **HTTPS** — put a reverse proxy (Nginx, Traefik, Caddy) in front that handles TLS and forwards to port 80.
5. **APP_DEBUG=false** — always off in production.
6. **Volumes** — `mysql_data` and `redis_data` are named Docker volumes. Back them up before major updates.

---

## Traditional Deployment (VPS / Shared Hosting)

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
