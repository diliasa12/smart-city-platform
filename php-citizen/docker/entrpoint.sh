#!/bin/sh
set -e

echo "==> Menjalankan migrasi database..."
php artisan migrate --force

echo "==> Menjalankan seeder..."
php artisan db:seed --force

echo "==> Clear cache..."
php artisan config:clear
php artisan cache:clear

echo "==> Menjalankan supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf