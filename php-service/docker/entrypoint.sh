#!/bin/bash 
 
# Jalankan migrasi (tanpa --seed) 
echo "Menjalankan migrasi database..." 
php artisan migrate --force 
 
# Jalankan command bawaan (misalnya start supervisord) 
exec "$@" 
