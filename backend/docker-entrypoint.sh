#!/bin/sh
set -e

# Ensure required storage and cache directories exist for the non-root user
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         storage/backups \
         bootstrap/cache

# Execute the container command
exec "$@"
