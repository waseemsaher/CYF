#!/usr/bin/env bash
# ==============================================================================
# Database Restore Script for Codeera Platform (Docker Compose Flow)
# ==============================================================================
# Executes 'php artisan db:restore' inside the running 'app' container.
# Restores a compressed MySQL backup from S3 object storage or local storage.
#
# Examples:
#   ./scripts/restore.sh --from-s3 --force
#   ./scripts/restore.sh backups/cyf_db_20260928_120000.sql.gz --force
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/cyf}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

cd "${APP_DIR}"

if command -v docker &> /dev/null && [[ -f "${COMPOSE_FILE}" ]]; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Executing database restore inside Docker 'app' container..."
    docker compose -f "${COMPOSE_FILE}" exec -T app php artisan db:restore "$@"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Restore complete."
    exit 0
fi

# Fallback if executed directly inside the container
if [[ -f "artisan" ]]; then
    php artisan db:restore "$@"
    exit 0
fi

echo "Error: Unable to locate docker compose or artisan."
exit 1
