#!/usr/bin/env bash
# ==============================================================================
# Production Initialization & Optimization Script for Codeera Backend
# Run this on your production host after starting Docker containers:
#   bash scripts/prod-init.sh
# ==============================================================================

set -euo pipefail

echo "=========================================================="
echo " Starting CYF Production Backend Initialization"
echo "=========================================================="

# 1. Generate app key if missing
echo "--> Checking APP_KEY..."
docker compose -f docker-compose.prod.yml exec backend php artisan key:generate --force || true

# 2. Run database migrations
echo "--> Running database migrations..."
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# 3. Seed reference data (academic years, departments, roles, terms, default courses)
echo "--> Seeding reference data and initial catalog..."
docker compose -f docker-compose.prod.yml exec backend php artisan db:seed --force

# 4. Create public storage symlink
echo "--> Creating storage symlink..."
docker compose -f docker-compose.prod.yml exec backend php artisan storage:link || true

# 5. Cache configurations, routes, and views for production performance
echo "--> Optimizing application caches (config, routes, views)..."
docker compose -f docker-compose.prod.yml exec backend php artisan optimize

# 6. Verify security configuration
echo "--> Verifying production security configuration..."
docker compose -f docker-compose.prod.yml exec backend php artisan production:verify

# 7. Restart queue worker to pick up fresh code and caches
echo "--> Restarting queue workers..."
docker compose -f docker-compose.prod.yml exec backend php artisan queue:restart

echo "=========================================================="
echo "✅ CYF Production Backend Initialized Successfully!"
echo "   Test health check: curl http://127.0.0.1/up"
echo "=========================================================="
