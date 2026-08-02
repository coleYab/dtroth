#!/usr/bin/env bash
#
# Troth Verify - shared hosting installer
# Run this script from the application root directory:
#   bash install.sh
#
# It performs every step needed to get the app running in production:
#   .env setup, app key, composer, database, migrations, seeders,
#   frontend build, Wayfinder routes, storage link, caches, permissions.
#
# Optional environment variables (all with sensible defaults):
#   APP_URL          - public URL of the app (prompted if not provided)
#   INSTALL_WITH_DEV - set to 1 to install dev dependencies too
#   SKIP_FRONTEND    - set to 1 to skip npm build (e.g. if built locally)
#   SKIP_CACHING     - set to 1 to skip config/route/view caching

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info()  { echo -e "${GREEN}[install]${NC} $*"; }
warn()  { echo -e "${YELLOW}[warn]${NC} $*"; }
fail()  { echo -e "${RED}[error]${NC} $*" >&2; exit 1; }

# ---------------------------------------------------------------- sanity checks

command -v php >/dev/null 2>&1 || fail "PHP not found in PATH. Install PHP 8.3+ first."

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
info "PHP $PHP_VERSION detected"

[ -f "artisan" ] || fail "artisan not found. Run this script from the application root."
[ -f "composer.json" ] || fail "composer.json not found. Run this script from the application root."

# ------------------------------------------------------------------- .env setup

if [ ! -f ".env" ]; then
    [ -f ".env.example" ] || fail ".env.example not found."
    cp .env.example .env
    info "Created .env from .env.example"
else
    info ".env already exists - keeping it"
fi

# APP_URL (prompt once if not given)
if [ -z "${APP_URL:-}" ] && ! grep -q "^APP_URL=" .env; then
    fail "APP_URL is not set. Re-run with: APP_URL=https://yourdomain.com bash install.sh"
fi

if [ -n "${APP_URL:-}" ]; then
    case "$APP_URL" in
        http://*|https://*) ;;
        *) fail "APP_URL must start with http:// or https://" ;;
    esac
    sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    info "Set APP_URL=${APP_URL}"
fi

# Force production values
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
info "Set APP_ENV=production and APP_DEBUG=false"

if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
    info "Generated application key"
else
    info "APP_KEY already present - keeping it"
fi

# ------------------------------------------------------------------ composer deps

COMPOSER_FLAGS=(install --no-interaction --prefer-dist --optimize-autoloader)
if [ "${INSTALL_WITH_DEV:-0}" != "1" ]; then
    COMPOSER_FLAGS+=(--no-dev)
fi

if command -v composer >/dev/null 2>&1; then
    info "Running: composer ${COMPOSER_FLAGS[*]}"
    composer "${COMPOSER_FLAGS[@]}"
else
    warn "composer not found in PATH - trying via php"
    [ -f "composer.phar" ] || fail "Neither composer nor composer.phar found."
    php composer.phar "${COMPOSER_FLAGS[@]}"
fi

# ----------------------------------------------------------------------- database

if grep -q "^DB_CONNECTION=sqlite" .env; then
    if [ ! -f "database/database.sqlite" ]; then
        touch database/database.sqlite
        info "Created database/database.sqlite"
    else
        info "SQLite database already exists"
    fi
fi

info "Running migrations..."
php artisan migrate --force

info "Seeding database (idempotent - creates demo user, presets, sandbox identities)..."
php artisan db:seed --force

# ------------------------------------------------------------------ wayfinder files

info "Generating Wayfinder routes..."
php artisan wayfinder:generate || warn "wayfinder:generate failed - run it manually if routes are missing"

# ------------------------------------------------------------------- frontend build

if [ "${SKIP_FRONTEND:-0}" = "1" ]; then
    warn "SKIP_FRONTEND=1 - skipping npm build. Upload a built public/build directory instead."
elif command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
    info "Installing npm dependencies..."
    if npm ci >/dev/null 2>&1; then
        :
    else
        warn "npm ci failed - falling back to npm install"
        npm install
    fi
    info "Building frontend assets..."
    npm run build
else
    warn "node/npm not found - skipping frontend build. Build locally and upload public/build, then re-run: SKIP_FRONTEND=1 bash install.sh"
fi

# ------------------------------------------------------------------ storage & caches

info "Linking storage..."
php artisan storage:link

info "Caching configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

info "Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || warn "Could not chmod storage - set writable permissions manually"
[ -f "storage/.gitignore" ] || touch storage/.gitignore

# remove stale Vite dev-server pointer if present
if [ -f "public/hot" ]; then
    rm -f public/hot
    info "Removed stale public/hot (Vite dev pointer)"
fi

# ------------------------------------------------------------------------ summary

info "Installation complete!"
echo ""
echo "  Website : ${APP_URL:-your domain}"
echo "  Admin   : ${APP_URL:-https://yourdomain.com}/login  (demo user: dev@example.com / password)"
echo ""
echo "Add these entries to your hosting cron jobs (adjust the path):"
echo ""
echo "  * * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
echo "  * * * * * cd ${APP_DIR} && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1"
echo ""
echo "Scheduled jobs configured in the app:"
echo "  - usage:rollup  (daily at 00:30)"
echo "  - status:check  (every 5 minutes)"
