FROM php:8.4-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
  unzip git curl \
  && docker-php-ext-install pdo pdo_mysql sockets \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

EXPOSE 8001

HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:8001/health || exit 1

CMD ["php", "-S", "0.0.0.0:8001", "-t", "public"]