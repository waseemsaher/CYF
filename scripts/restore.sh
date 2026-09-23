#!/usr/bin/env bash
# ==============================================================================
# Database Restore Script for Al-Azhar FCAI Course Platform
# Restores a compressed MySQL backup created by backup.sh.
# ==============================================================================

set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <path-to-backup.sql.gz> [--force]"
    exit 1
fi

BACKUP_FILE="$1"
FORCE="${2:-}"
ENV_FILE="${ENV_FILE:-/var/www/cyf/backend/.env}"

if [[ ! -f "${BACKUP_FILE}" ]]; then
    echo "Error: Backup file '${BACKUP_FILE}' not found."
    exit 1
fi

if [[ "${FORCE}" != "--force" ]]; then
    echo "WARNING: This will overwrite the existing database with '${BACKUP_FILE}'."
    read -r -p "Are you sure you want to proceed? [y/N]: " CONFIRM
    if [[ "${CONFIRM}" != "y" && "${CONFIRM}" != "Y" ]]; then
        echo "Restore cancelled."
        exit 0
    fi
fi

# Extract DB credentials
if [[ -f "${ENV_FILE}" ]]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_USERNAME=$(grep "^DB_USERNAME=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "")
    DB_HOST=$(grep "^DB_HOST=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "127.0.0.1")
    DB_PORT=$(grep "^DB_PORT=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "3306")
fi

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Restoring database from '${BACKUP_FILE}'..."

if [[ -n "${DB_PASSWORD:-}" ]]; then
    MYSQL_PWD="${DB_PASSWORD}" gunzip -c "${BACKUP_FILE}" | mysql \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        "${DB_DATABASE}"
else
    gunzip -c "${BACKUP_FILE}" | mysql \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        "${DB_DATABASE}"
fi

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Database restored successfully."

# Clear cache after restoring data
if [[ -d "/var/www/cyf/backend" ]]; then
    cd /var/www/cyf/backend
    php artisan optimize:clear || true
fi

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Restore finished."
