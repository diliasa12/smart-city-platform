FROM php:8.4-fpm
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
  bash curl nginx supervisor default-mysql-client unzip git \
  && docker-php-ext-install pdo pdo_mysql bcmath sockets \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY . .

# Tidak perlu composer install, vendor sudah ada
RUN composer dump-autoload --optimize --no-dev --no-scripts
RUN composer dump-autoload --optimize --no-dev --no-scripts \
  && rm -f bootstrap/cache/packages.php \
  && rm -f bootstrap/cache/services.php
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
  storage/logs bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i -e 's/\r//g' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
  CMD curl -f http://localhost:8000/api/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]