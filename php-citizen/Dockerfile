FROM php:8.4-fpm

WORKDIR /var/www/html

# Install dependencies sistem menggunakan apt-get (Debian/Ubuntu)
RUN apt-get update && apt-get install -y \
    bash \
    curl \
    nginx \
    supervisor \
    default-mysql-client \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql bcmath sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy semua file project
COPY . .

# Install PHP dependencies
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader -vvv

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
    CMD curl -f http://localhost:8000/api/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]