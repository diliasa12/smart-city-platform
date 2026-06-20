## 🚀 Update Feature: Admin CRUD (Zones & Rooms) & Docker Support

Fitur ini menambahkan konfigurasi infrastruktur Docker untuk `php-service` (Laravel) dan menyempurnakan endpoint CRUD untuk Admin.

### 🛠️ Cara Menjalankan di Lokal (Setelah Pull)

1. Buka terminal di folder root proyek (`smart-city-platform`).
2. Build dan nyalakan container:
   ```bash
   docker compose up -d --build php-service mysql
   ```

### Lakukan setup awal Laravel (Hanya perlu dijalankan sekali setelah container menyala)
``` bash
docker exec -it smartcity-service composer install
docker exec -it smartcity-service php artisan key:generate
docker exec -it smartcity-service php artisan migrate
```

### Endpoint Baru (Test via Postman)
   |Method|	  Endpoint	                  |      Deskripsi                  |
   |------|-------------------------------|---------------------------------|
   |POST	  |http://localhost:8000/api/admin/zones |	 Membuat zona/wilayah baru         |
   |POST  |http://localhost:8000/api/admin/rooms	  |  Membuat ruangan baru yang berelasi dengan zona|
  
