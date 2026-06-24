#!/bin/sh
set -e

echo "[Entrypoint] Memulai php-service..."

# ── 1. Tunggu MySQL siap ───────────────────────────────────────
echo "[Entrypoint] Menunggu koneksi database..."
until php -r "
  try {
    new PDO(
      'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
      getenv('DB_USERNAME'),
      getenv('DB_PASSWORD')
    );
    exit(0);
  } catch (Exception \$e) {
    exit(1);
  }
" 2>/dev/null; do
  echo "[Entrypoint] Database belum siap, retry dalam 3 detik..."
  sleep 3
done
echo "[Entrypoint] Database terhubung."

# ── 2. Generate app key jika belum ada ────────────────────────
if [ -z "$APP_KEY" ]; then
  echo "[Entrypoint] APP_KEY kosong, generate..."
  php artisan key:generate --force
fi

# ── 3. Bersihkan bootstrap cache lama ────────────────────────
echo "[Entrypoint] Membersihkan bootstrap cache..."
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php

# ── 4. Discover packages ──────────────────────────────────────
echo "[Entrypoint] Discover packages..."
php artisan package:discover --ansi

# ── 5. Jalankan migrasi ───────────────────────────────────────
echo "[Entrypoint] Menjalankan migrasi..."
php artisan migrate --force --no-interaction

# ── 6. Buat storage symlink ────────────────────────────────────
echo "[Entrypoint] Membuat storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# ── 7. Cache konfigurasi ──────────────────────────────────────
if [ "$APP_ENV" = "production" ]; then
  echo "[Entrypoint] Cache config, route, view..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
else
  echo "[Entrypoint] Mode development — skip caching."
  php artisan config:clear
  php artisan view:clear
fi

# ── 8. Start supervisor (nginx + php-fpm + queue worker) ──────
echo "[Entrypoint] Menjalankan supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisord.conf