#!/usr/bin/env bash
# ==============================================================================
# Automated Database Backup Script for Codeera Platform (Docker Compose Flow)
# ==============================================================================
# Executes 'php artisan db:backup' inside the running 'app' container.
# Dumps MySQL to a compressed archive, rotates local retention, and uploads to S3.
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/cyf}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

cd "${APP_DIR}"

if command -v docker &> /dev/null && [[ -f "${COMPOSE_FILE}" ]]; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Triggering database backup inside Docker 'app' container..."
    docker compose -f "${COMPOSE_FILE}" exec -T app php artisan db:backup "$@"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup complete."
    exit 0
fi

# Fallback if executed directly inside the container
if [[ -f "artisan" ]]; then
    php artisan db:backup "$@"
    exit 0
fi

echo "Error: Unable to locate docker compose or artisan."
exit 1
