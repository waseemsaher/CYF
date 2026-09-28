#!/usr/bin/env bash
# ==============================================================================
# Automated Database Backup Script for Codeera Platform
# Backs up MySQL to a timestamped compressed archive, rotates local retention,
# and uploads to AWS S3 (and Oracle Object Storage) via Laravel Storage / AWS CLI.
# ==============================================================================

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/cyf}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKEND_DIR="${BACKEND_DIR:-/var/www/cyf/backend}"
ENV_FILE="${ENV_FILE:-${BACKEND_DIR}/.env}"

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting database backup routine..."

mkdir -p "${BACKUP_DIR}"

# 1. Prefer Laravel Artisan db:backup if backend directory exists
if [[ -f "${BACKEND_DIR}/artisan" ]]; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Executing 'php artisan db:backup'..."
    cd "${BACKEND_DIR}"
    php artisan db:backup --path="${BACKUP_DIR}" --retention="${RETENTION_DAYS}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup routine finished successfully via Artisan."
    exit 0
fi

# 2. Standalone fallback: mysqldump + AWS CLI / s3cmd
if [[ -f "${ENV_FILE}" ]]; then
    DB_DATABASE=$(grep "^DB_DATABASE=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_USERNAME=$(grep "^DB_USERNAME=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "cyf")
    DB_PASSWORD=$(grep "^DB_PASSWORD=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "")
    DB_HOST=$(grep "^DB_HOST=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "127.0.0.1")
    DB_PORT=$(grep "^DB_PORT=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "3306")
    AWS_BUCKET=$(grep "^AWS_BUCKET=" "${ENV_FILE}" | cut -d '=' -f2- | tr -d ' "' || echo "")
fi

BACKUP_FILE="${BACKUP_DIR}/cyf_db_${TIMESTAMP}.sql.gz"

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

# Upload to AWS S3
if [[ -n "${AWS_BUCKET:-}" ]] && command -v aws &> /dev/null; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Uploading to AWS S3 (s3://${AWS_BUCKET}/backups/)..."
    aws s3 cp "${BACKUP_FILE}" "s3://${AWS_BUCKET}/backups/"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Cloud upload complete."
fi

# Rotate old local backups
echo "[$(date +'%Y-%m-%d %H:%M:%S')] Cleaning up local backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -name "cyf_db_*.sql.gz" -mtime +"${RETENTION_DAYS}" -delete

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup routine finished successfully."
