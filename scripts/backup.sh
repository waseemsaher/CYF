#!/usr/bin/env bash
# ==============================================================================
# Automated Database Backup Script for Al-Azhar FCAI Course Platform
# Backs up MySQL to a timestamped compressed archive, rotates local retention,
# and optionally syncs to DigitalOcean Spaces (S3-compatible storage).
# ==============================================================================

set -euo pipefail

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/cyf}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ENV_FILE="${ENV_FILE:-/var/www/cyf/backend/.env}"

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting database backup..."

# Ensure backup directory exists
mkdir -p "${BACKUP_DIR}"

# Extract DB credentials from .env if not already set in environment
if [[ -f "${ENV_FILE}" ]]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_USERNAME=$(grep "^DB_USERNAME=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "")
    DB_HOST=$(grep "^DB_HOST=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "127.0.0.1")
    DB_PORT=$(grep "^DB_PORT=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "3306")
    DO_SPACES_BUCKET=$(grep "^DO_SPACES_BUCKET=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "")
fi

BACKUP_FILE="${BACKUP_DIR}/cyf_db_${TIMESTAMP}.sql.gz"

# Perform mysqldump with single transaction (consistent point-in-time)
if [[ -n "${DB_PASSWORD:-}" ]]; then
    MYSQL_PWD="${DB_PASSWORD}" mysqldump \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        "${DB_DATABASE}" | gzip -9 > "${BACKUP_FILE}"
else
    mysqldump \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        "${DB_DATABASE}" | gzip -9 > "${BACKUP_FILE}"
fi

FILESIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup created: ${BACKUP_FILE} (${FILESIZE})"

# Optional: Upload to DigitalOcean Spaces if configured
if [[ -n "${DO_SPACES_BUCKET:-}" ]] && command -v s3cmd &> /dev/null; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Uploading to DigitalOcean Spaces (s3://${DO_SPACES_BUCKET}/backups/)..."
    s3cmd put "${BACKUP_FILE}" "s3://${DO_SPACES_BUCKET}/backups/"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Cloud upload complete."
fi

# Rotate old local backups
echo "[$(date +'%Y-%m-%d %H:%M:%S')] Cleaning up local backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -name "cyf_db_*.sql.gz" -mtime +"${RETENTION_DAYS}" -delete

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup routine finished successfully."
