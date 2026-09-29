# ==============================================================================
# Multi-Stage Production Dockerfile for Codeera Backend (PHP 8.3 FPM Alpine)
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Build production Composer vendor dependencies
# ------------------------------------------------------------------------------
FROM composer:2 AS composer-builder

ARG INSTALL_DEV=false

WORKDIR /app

# Copy Composer manifests
COPY composer.json composer.lock ./

# Install vendor dependencies (respecting INSTALL_DEV flag)
RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs; \
    else \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs; \
    fi

# Copy application source code
COPY . .

# Generate optimized autoloader
RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer dump-autoload --optimize; \
    else \
        composer dump-autoload --optimize --no-dev --classmap-authoritative; \
    fi

# ------------------------------------------------------------------------------
# Stage 2: Production PHP 8.4 FPM Alpine Runtime
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS runner

WORKDIR /var/www/html

# Install runtime packages:
# - bash & curl for health checks & administrative scripts
# - mariadb-client (mysqldump & mysql CLI) for db:backup and db:restore
# - gzip for compressed database dumps
# - fcgi for php-fpm container health checks
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
        bash \
        curl \
        gzip \
        mariadb-client \
        fcgi \
    && install-php-extensions \
        pdo_mysql \
        gd \
        bcmath \
        intl \
        pcntl \
        zip \
        exif \
        redis \
        opcache

# Copy custom PHP and PHP-FPM configuration files
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/docker-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Ensure directory structure and ownership
RUN mkdir -p /var/www/html \
    && chown -R www-data:www-data /var/www/html

# Copy application and vendor from composer-builder stage
COPY --chown=www-data:www-data --from=composer-builder /app /var/www/html

# Create persistent storage directories with proper permissions for non-root www-data
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/storage/backups \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Switch to non-root user
USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
