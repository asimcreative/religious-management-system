#!/usr/bin/env bash

# =============================================================================
# RAMS — Manual Rollback Script
# =============================================================================
# Usage:
#   bash scripts/rollback.sh <commit-hash>
#
# Example:
#   bash scripts/rollback.sh a1b2c3d4
#
# Find the target commit from:
#   git log --oneline
# or from the rollback reference logged in:
#   storage/logs/deploy.log
# =============================================================================

set -euo pipefail

# ─── Colour helpers ──────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Colour

log_info()    { echo -e "${BLUE}[INFO]${NC}    $*"; }
log_ok()      { echo -e "${GREEN}[OK]${NC}      $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC}    $*"; }
log_error()   { echo -e "${RED}[ERROR]${NC}   $*"; }

# ─── Validate input ──────────────────────────────────────────────────────────
if [[ $# -lt 1 ]]; then
    log_error "Usage: bash scripts/rollback.sh <commit-hash>"
    log_info  "Find the commit from: git log --oneline"
    log_info  "Or check: storage/logs/deploy.log (look for 'Rollback ref' lines)"
    exit 1
fi

TARGET_COMMIT="$1"

# Validate commit hash format (7–40 hex chars)
if ! [[ "$TARGET_COMMIT" =~ ^[a-f0-9]{7,40}$ ]]; then
    log_error "Invalid commit hash: '$TARGET_COMMIT'"
    log_info  "A commit hash contains only hex characters (a-f, 0-9), 7–40 chars long."
    exit 1
fi

# ─── Confirmation prompt ─────────────────────────────────────────────────────
CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")

echo ""
echo -e "${YELLOW}══════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  RAMS — ROLLBACK CONFIRMATION                     ${NC}"
echo -e "${YELLOW}══════════════════════════════════════════════════${NC}"
echo ""
log_warn "You are about to roll back RAMS to commit: $TARGET_COMMIT"
log_info "Current commit: $CURRENT_COMMIT"
echo ""
log_warn "This will:"
echo "  1. Enable maintenance mode"
echo "  2. Reset code to commit $TARGET_COMMIT"
echo "  3. Re-install Composer dependencies"
echo "  4. Run migrations (rollback if migration was added)"
echo "  5. Clear and re-cache Laravel caches"
echo "  6. Terminate Horizon (Supervisor will restart)"
echo "  7. Disable maintenance mode"
echo ""
read -r -p "Type 'ROLLBACK' to confirm: " CONFIRM

if [[ "$CONFIRM" != "ROLLBACK" ]]; then
    log_info "Rollback cancelled."
    exit 0
fi

# ─── Locate binaries ─────────────────────────────────────────────────────────
PHP_BIN="${DEPLOY_PHP_BINARY:-}"
if [[ -z "$PHP_BIN" ]]; then
    for candidate in php84 php83 php82 php; do
        if command -v "$candidate" &>/dev/null; then
            PHP_BIN=$(command -v "$candidate")
            break
        fi
    done
fi

if [[ -z "$PHP_BIN" ]]; then
    log_error "PHP binary not found. Set DEPLOY_PHP_BINARY env variable."
    exit 1
fi

COMPOSER_BIN="${DEPLOY_COMPOSER_BINARY:-}"
if [[ -z "$COMPOSER_BIN" ]]; then
    for candidate in /usr/local/bin/composer /usr/bin/composer composer; do
        if command -v "$candidate" &>/dev/null 2>&1; then
            COMPOSER_BIN="$candidate"
            break
        fi
    done
fi

log_info "PHP:      $PHP_BIN"
log_info "Composer: $COMPOSER_BIN"

# ─── Rollback steps ──────────────────────────────────────────────────────────
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ROLLBACK_LOG="$PROJECT_ROOT/storage/logs/deploy.log"

run_step() {
    local step_name="$1"
    local command="$2"
    log_info "Step [$step_name]: $command"
    if eval "$command"; then
        log_ok "Step [$step_name]: OK"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ROLLBACK STEP [$step_name]: OK" >> "$ROLLBACK_LOG"
    else
        log_error "Step [$step_name]: FAILED"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ROLLBACK STEP [$step_name]: FAILED" >> "$ROLLBACK_LOG"
        log_warn "Attempting to bring site back up..."
        "$PHP_BIN" "$PROJECT_ROOT/artisan" up --no-interaction || true
        exit 1
    fi
}

echo ""
echo "[$(date '+%Y-%m-%d %H:%M:%S')] ROLLBACK START to $TARGET_COMMIT (from $CURRENT_COMMIT)" >> "$ROLLBACK_LOG"

run_step "maintenance:on"    "$PHP_BIN $PROJECT_ROOT/artisan down --no-interaction"
run_step "git:reset"         "git -C $PROJECT_ROOT reset --hard $TARGET_COMMIT"
run_step "composer:install"  "$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --working-dir=$PROJECT_ROOT"
run_step "migrate"           "$PHP_BIN $PROJECT_ROOT/artisan migrate --force --no-interaction"
run_step "optimize:clear"    "$PHP_BIN $PROJECT_ROOT/artisan optimize:clear --no-interaction"
run_step "optimize"          "$PHP_BIN $PROJECT_ROOT/artisan optimize --no-interaction"
run_step "horizon:terminate" "$PHP_BIN $PROJECT_ROOT/artisan horizon:terminate --no-interaction"
run_step "maintenance:off"   "$PHP_BIN $PROJECT_ROOT/artisan up --no-interaction"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] ROLLBACK SUCCESS to $TARGET_COMMIT" >> "$ROLLBACK_LOG"

echo ""
echo -e "${GREEN}══════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ROLLBACK COMPLETE                                ${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════${NC}"
echo ""
log_ok "Rolled back to commit: $TARGET_COMMIT"
log_info "Verify the site at: https://rams.babiesworld.com.pk"
echo ""
