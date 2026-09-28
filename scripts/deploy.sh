#!/usr/bin/env bash
# ==============================================================================
# Production Deployment Script for Codeera Backend (Docker Compose Flow)
# ==============================================================================
# Architecture: Builds images directly on the target host (native CPU architecture:
# x86_64 on AWS EC2, aarch64/ARM64 on Oracle Cloud Always Free Ampere A1).
#
# Rollback Procedure:
# If deployment fails or health check fails:
#   git checkout <PREVIOUS_COMMIT_HASH>
#   docker compose -f docker-compose.prod.yml build
#   docker compose -f docker-compose.prod.yml up -d
#   docker compose -f docker-compose.prod.yml exec -T app php artisan optimize
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/cyf}"
BRANCH="${1:-main}"
COMPOSE_FILE="docker-compose.prod.yml"

echo "=========================================================="
echo " Starting Codeera Docker Deployment at $(date +'%Y-%m-%d %H:%M:%S')"
echo " Branch/Target: ${BRANCH}"
echo " Directory:     ${APP_DIR}"
echo " Compose File:  ${COMPOSE_FILE}"
echo "=========================================================="

cd "${APP_DIR}"

# 0. Record previous commit for documented rollback
PREV_COMMIT=$(git rev-parse HEAD || echo "HEAD")
echo "--> Previous commit: ${PREV_COMMIT}"

# 1. Update source code from git
echo "--> Pulling latest source code from git repository..."
git fetch --all
git checkout "${BRANCH}"
git pull origin "${BRANCH}"
NEW_COMMIT=$(git rev-parse HEAD)
echo "--> Deployed commit: ${NEW_COMMIT}"

# 2. Acquire production Docker image (pull pre-built if configured, fallback to build)
echo "--> Acquiring production Docker images (${COMPOSE_FILE})..."
if ! docker compose -f "${COMPOSE_FILE}" pull app 2>/dev/null; then
    echo "--> Registry image not available, building production Docker image on host..."
    docker compose -f "${COMPOSE_FILE}" build
fi

# 3. Run database migrations
echo "--> Executing database migrations..."
docker compose -f "${COMPOSE_FILE}" run --rm app php artisan migrate --force

# 4. Bring up stack with updated containers
echo "--> Starting updated service containers in background..."
docker compose -f "${COMPOSE_FILE}" up -d

# 5. Optimize configuration, routes, and views
echo "--> Caching Laravel configurations, routes, and views..."
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan optimize

# 6. Restart queue workers to pick up fresh code
echo "--> Restarting queue workers..."
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan queue:restart

# 7. Deployment Health Validation
echo "--> Validating deployment health..."
sleep 3

MAX_RETRIES=5
RETRY_COUNT=0
HEALTHY=false

while [[ ${RETRY_COUNT} -lt ${MAX_RETRIES} ]]; do
    if docker compose -f "${COMPOSE_FILE}" exec -T web wget --no-verbose --tries=1 --spider http://127.0.0.1:80/up > /dev/null 2>&1; then
        HEALTHY=true
        break
    fi
    echo "    Waiting for services to become healthy (attempt $((RETRY_COUNT + 1))/${MAX_RETRIES})..."
    sleep 3
    RETRY_COUNT=$((RETRY_COUNT + 1))
done

if [[ "${HEALTHY}" == "true" ]]; then
    echo "✅ Health check passed: API and Reverse Proxy are healthy."
else
    echo "⚠️ Warning: Health check endpoint /up did not return 200 within expected time."
    echo "Review container logs with: docker compose -f ${COMPOSE_FILE} logs --tail=100"
    echo ""
    echo "To rollback immediately, run:"
    echo "  git checkout ${PREV_COMMIT}"
    echo "  docker compose -f ${COMPOSE_FILE} build"
    echo "  docker compose -f ${COMPOSE_FILE} up -d"
    echo "  docker compose -f ${COMPOSE_FILE} exec -T app php artisan optimize"
    exit 1
fi

echo "=========================================================="
echo " Codeera Docker Deployment completed successfully at $(date +'%Y-%m-%d %H:%M:%S')"
echo "=========================================================="
