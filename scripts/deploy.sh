#!/usr/bin/env bash
# ==============================================================================
# Production Deployment Script for Al-Azhar FCAI Course Platform
# Orchestrates git pull, dependency installation, migrations, cache warming,
# asset compilation, process restarts, and health validation.
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/cyf}"
BRANCH="${1:-main}"

echo "=========================================================="
echo " Starting CYF Deployment at $(date +'%Y-%m-%d %H:%M:%S')"
echo " Branch/Target: ${BRANCH}"
echo " Directory: ${APP_DIR}"
echo "=========================================================="

cd "${APP_DIR}"

# 1. Update source code
echo "--> Fetching latest changes from git..."
git fetch --all
git checkout "${BRANCH}"
git pull origin "${BRANCH}"

# 2. Put backend in maintenance mode (with secret bypass)
echo "--> Enabling maintenance mode..."
cd "${APP_DIR}/backend"
php artisan down --secret="cyf-deploy-bypass" --render="errors.503" || true

# 3. Backend dependencies & optimizations
echo "--> Installing production Composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "--> Running database migrations..."
php artisan migrate --force

echo "--> Caching Laravel configurations, routes, and views..."
php artisan optimize

echo "--> Verifying production environment and security configurations..."
php artisan production:verify

echo "--> Restarting queue workers..."
php artisan queue:restart

# 4. Frontend build
echo "--> Building SvelteKit production bundle..."
cd "${APP_DIR}/frontend"
npm ci --prefer-offline
npm run build

# 5. Restart services via Supervisor and reload Web Server
echo "--> Restarting application processes..."
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart cyf-worker:* || true
    sudo supervisorctl restart cyf-frontend || true
fi

if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm || true
    sudo nginx -t && sudo systemctl reload nginx || true
fi

# 6. Bring backend out of maintenance mode
echo "--> Disabling maintenance mode..."
cd "${APP_DIR}/backend"
php artisan up

# 7. Health check validation
echo "--> Validating deployment health..."
sleep 2
if command -v curl &> /dev/null; then
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/up || echo "000")
    if [[ "${HTTP_STATUS}" == "200" ]]; then
        echo "✅ Health check passed (HTTP 200 OK)."
    else
        echo "⚠️ Health check returned HTTP ${HTTP_STATUS}. Review /var/log/nginx/ and storage/logs/laravel.log"
    fi
fi

echo "=========================================================="
echo " CYF Deployment completed successfully at $(date +'%Y-%m-%d %H:%M:%S')"
echo "=========================================================="
