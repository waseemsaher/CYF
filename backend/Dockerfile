FROM php:8.4-cli-alpine

WORKDIR /var/www/html

# Install required system packages and php84-fpm + extensions
RUN apk add --no-cache \
    bash \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    sqlite-dev \
    php84-fpm \
    php84-pdo_mysql \
    php84-mbstring \
    php84-intl \
    php84-gd \
    php84-zip \
    php84-opcache \
    php84-bcmath \
    php84-pcntl \
    php84-exif \
    php84-session \
    php84-tokenizer \
    php84-xml \
    php84-ctype \
    php84-curl \
    php84-fileinfo \
    php84-iconv \
    php84-phar \
    php84-simplexml \
    php84-xmlwriter \
    php84-dom \
    php84-openssl \
    && mkdir -p /usr/local/sbin \
    && ln -sf /usr/sbin/php-fpm84 /usr/local/sbin/php-fpm \
    && ln -sf /usr/sbin/php-fpm84 /usr/local/bin/php-fpm \
    && sed -i 's/^listen = 127.0.0.1:9000/listen = 9000/' /etc/php84/php-fpm.d/www.conf \
    && sed -i 's/^;clear_env = no/clear_env = no/' /etc/php84/php-fpm.d/www.conf

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        zip \
        intl \
        exif \
        pcntl \
        bcmath \
        gd \
        opcache

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["php-fpm", "-F"]
