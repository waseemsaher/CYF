#!/bin/sh
set -e

# Ensure Laravel directories exist and have proper permissions
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache \
         storage/logs \
         bootstrap/cache \
         database

chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# If vendor directory or autoloader does not exist, install composer dependencies
if [ ! -f "vendor/autoload.php" ]; then
    echo "vendor/autoload.php not found. Running composer install..."
    composer install --prefer-dist --no-interaction --no-progress
fi

# If .env does not exist, copy from .env.example or create minimal .env
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo ".env not found. Copying from .env.example..."
        cp .env.example .env
    else
        echo ".env and .env.example not found. Creating default .env..."
        cat << 'EOF' > .env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
EOF
    fi

    if [ -f "artisan" ]; then
        php artisan key:generate --ansi || true
    fi
fi

# Ensure SQLite file exists if SQLite is configured
if grep -q "DB_CONNECTION=sqlite" .env 2>/dev/null; then
    touch database/database.sqlite
    chmod 666 database/database.sqlite 2>/dev/null || true
fi

exec "$@"
