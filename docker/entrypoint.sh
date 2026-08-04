#!/usr/bin/env bash
# =================================================================
# RAMS — Docker Entrypoint
#
# Runs as root so it can write to Docker-managed named volumes,
# then uses su-exec to drop privileges before exec'ing the final
# process as the www user (UID 1000).
#
# Behaviour differs by command:
#
#   php-fpm  (app container)
#       1. Validate APP_KEY is set
#       2. Populate app_public volume from /var/www/public-init
#          (only if public/index.php is missing — first run only)
#       3. php artisan package:discover
#       4. php artisan optimize        (config / route / view / event cache)
#       5. php artisan migrate --force (idempotent)
#       6. php artisan storage:link
#       7. exec su-exec www php-fpm
#
#   php artisan horizon / schedule:work  (horizon / scheduler containers)
#       → skip init (app container already ran it)
#       → exec su-exec www <command>
#
# =================================================================

set -euo pipefail

APP_DIR="/var/www/html"
PUBLIC_INIT="/var/www/public-init"

log() {
    echo "[RAMS] $*"
}

# -----------------------------------------------------------------
# Helper: wait for a TCP port to accept connections
# -----------------------------------------------------------------
wait_for() {
    local host="$1" port="$2" label="$3"
    local retries=30 delay=2

    log "Waiting for ${label} (${host}:${port})..."
    until nc -z "$host" "$port" 2>/dev/null; do
        retries=$((retries - 1))
        if [ "$retries" -le 0 ]; then
            log "ERROR: ${label} did not become available in time."
            exit 1
        fi
        sleep "$delay"
    done
    log "${label} is ready."
}

# -----------------------------------------------------------------
# Full initialisation — only for the php-fpm (app) container
# -----------------------------------------------------------------
init_app() {
    # 1. Validate required environment variables
    if [ -z "${APP_KEY:-}" ]; then
        log "ERROR: APP_KEY is not set."
        log "       Copy .env.docker to .env and run:"
        log "       php artisan key:generate --show"
        log "       Then add the output as APP_KEY= in your .env file."
        exit 1
    fi

    # 2. Wait for MySQL and Redis to be ready
    wait_for "${DB_HOST:-mysql}"    "${DB_PORT:-3306}"  "MySQL"
    wait_for "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}" "Redis"

    # 3. Populate the app_public volume on first run
    #    The volume is initially empty; Docker hides the image's
    #    /var/www/html/public behind the empty mount.
    #    We keep a second copy at /var/www/public-init for this.
    if [ ! -f "${APP_DIR}/public/index.php" ]; then
        log "Populating public volume from build image..."
        cp -r "${PUBLIC_INIT}/." "${APP_DIR}/public/"
        chown -R www:www "${APP_DIR}/public"
        log "Public volume populated."
    else
        log "Public volume already populated — skipping copy."
    fi

    # 4. Package discovery (composer --no-scripts was used at build time)
    log "Running package:discover..."
    su-exec www php "${APP_DIR}/artisan" package:discover --ansi

    # 5. Optimize (config / route / view / event caches)
    log "Running optimize..."
    su-exec www php "${APP_DIR}/artisan" optimize --ansi

    # 6. Database migrations (idempotent — safe on every restart)
    log "Running migrations..."
    su-exec www php "${APP_DIR}/artisan" migrate --force --ansi

    # 7. Storage symlink (public/storage → storage/app/public)
    log "Creating storage link..."
    su-exec www php "${APP_DIR}/artisan" storage:link --ansi 2>/dev/null || true

    log "Initialisation complete. Starting php-fpm..."
}

# -----------------------------------------------------------------
# Main
# -----------------------------------------------------------------
COMMAND="${1:-php-fpm}"

case "$COMMAND" in
    php-fpm)
        init_app
        exec su-exec www php-fpm
        ;;
    php)
        # Artisan commands (horizon, schedule:work, etc.)
        # App container has already done init; just drop to www and run.
        exec su-exec www "$@"
        ;;
    *)
        # Pass-through for any other commands (bash, sh, etc.)
        exec "$@"
        ;;
esac
