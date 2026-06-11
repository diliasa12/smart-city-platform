FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Install dependencies sistem
RUN apk add --no-cache \
    bash \
    curl \
    nginx \
    supervisor \
    mysql-client \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy semua file project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Buat folder yang dibutuhkan Laravel dan set permission
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Copy konfigurasi nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy konfigurasi supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
  CMD curl -f http://localhost:8000/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]