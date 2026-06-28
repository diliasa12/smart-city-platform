# 🏙️ Smart City Platform

Platform manajemen kota pintar berbasis arsitektur **microservices** yang mengintegrasikan IoT, Machine Learning, dan manajemen ruangan secara real-time.

## 🗺️ Arsitektur Sistem

```
                        ┌─────────────────────────────────────┐
                        │         API Gateway (:3000)          │
                        │    Express.js · JWT · Rate Limit     │
                        └────┬───────────┬────────────┬────────┘
                             │           │            │
              ┌──────────────▼──┐  ┌─────▼──────┐  ┌▼────────────────┐
              │  OAuth Server   │  │ PHP Service │  │   ML Service    │
              │  Node.js :3002  │  │ Laravel:8000│  │  FastAPI :5000  │
              └─────────────────┘  └──────┬──────┘  └────────┬────────┘
                                          │                   │
                              ┌───────────┴────────┐  ┌──────▼──────┐
                              │       MySQL         │  │  RabbitMQ   │
                              │      :3306          │  │  :5672      │
                              └─────────────────────┘  └─────────────┘
                                                               │
                                                    ┌──────────▼──────────┐
                                                    │    ML Worker        │
                                                    │ (RabbitMQ Consumer) │
                                                    └─────────────────────┘
              ┌───────────────────┐
              │   IoT Service     │
              │  Node.js :3000    │← MQTT (HiveMQ)
              └───────────────────┘
```

| Service | Teknologi | Port Host |
|---|---|---|
| API Gateway | Express.js + Node 20 | `3000` |
| OAuth Server | Node.js + OAuth2 | `5001` |
| PHP Service | Laravel 11 + PHP 8.4 | `8000` |
| IoT Service | Node.js + MQTT | `3001` |
| ML Service | FastAPI + Python 3.11 | `5000` |
| ML Worker | Python (RabbitMQ consumer) | — |
| MySQL | MySQL 8.0 | `3307` |
| RabbitMQ | RabbitMQ 3.12 | `5672`, `15672` |
| phpMyAdmin | phpMyAdmin latest | `8081` |

---

## ✅ Prerequisites

Pastikan semua tools berikut sudah terinstal sebelum memulai.

### Wajib (untuk menjalankan via Docker)

| Tool | Versi Minimum | Cek Versi |
|---|---|---|
| [Docker](https://docs.docker.com/get-docker/) | 24.x | `docker --version` |
| [Docker Compose](https://docs.docker.com/compose/install/) | v2.x | `docker compose version` |

### Opsional (untuk pengembangan lokal tanpa Docker)

| Tool | Versi Minimum | Cek Versi |
|---|---|---|
| [PHP](https://www.php.net/downloads) | 8.4 | `php --version` |
| [Composer](https://getcomposer.org/) | 2.x | `composer --version` |
| [Node.js](https://nodejs.org/) | 20.x (LTS) | `node --version` |
| [Python](https://www.python.org/downloads/) | 3.11+ | `python --version` |
| MySQL Client | 8.0+ | `mysql --version` |

---

## ⚙️ Setup Environment Variables

### 1. Root `.env` (Docker Compose)

File ini mengontrol variabel yang di-*inject* ke seluruh container oleh Docker Compose.

```bash
cp .env.example .env
```

Isi file `.env` dengan nilai berikut:

```env
# ── API GATEWAY & OAuth ──────────────────────────────────────
# Rahasia untuk signing JWT token. Gunakan string acak yang panjang.
# Contoh: openssl rand -hex 32
JWT_SECRET=ganti_dengan_secret_panjang_dan_acak

# ── MYSQL ────────────────────────────────────────────────────
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_DATABASE=smartcity

# ── PHP SERVICE (Laravel) ────────────────────────────────────
# Generate dengan: php artisan key:generate --show
# Format: base64:xxxx...
PHP_APP_KEY=base64:isi_dengan_output_php_artisan_key_generate

# ── URL ANTAR SERVICE (opsional, untuk kebutuhan eksternal) ──
PHP_URL=http://localhost:8000
ML_URL=http://localhost:5000
OAUTH_URL=http://localhost:5001
IOT_URL=http://localhost:3001
```

> **Catatan:** `MYSQL_ROOT_PASSWORD` dan `JWT_SECRET` harus sama di seluruh service. Jika belum punya `PHP_APP_KEY`, biarkan kosong dulu — Laravel akan meng-generate-nya otomatis saat container pertama kali berjalan.

---

### 2. `express-gateway/.env` (API Gateway)

```bash
cp express-gateway/.env.example express-gateway/.env
```

```env
PORT_GATEWAY=3000
PHP_URL=http://php-service:8000
OAUTH_URL=http://oauth-server:3002
ML_URL=http://python-ml:5000
JWT_SECRET=     # ← Sama dengan JWT_SECRET di root .env
```

> **Penting:** Saat dijalankan via Docker Compose, gunakan nama service Docker (bukan `localhost`) sebagai host, karena antar container berkomunikasi melalui network internal `smartcity-net`.

---

### 3. `php-service/.env` (Laravel Backend)

```bash
cp php-service/.env.example php-service/.env
```

Nilai minimal yang perlu diisi:

```env
APP_NAME=SmartCity
APP_ENV=local
APP_KEY=           # ← Akan di-generate otomatis oleh entrypoint.sh jika kosong
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_LEVEL=debug

# ── Database ─────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=mysql          # nama service di docker-compose
DB_PORT=3306
DB_DATABASE=smartcity
DB_USERNAME=root
DB_PASSWORD=rootpass   # ← Sama dengan MYSQL_ROOT_PASSWORD

# ── Session, Queue, Cache ────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
CACHE_STORE=database

# ── RabbitMQ ─────────────────────────────────────────────────
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672

# ── IoT Service ──────────────────────────────────────────────
NODEJS_IOT_SERVER_URL=http://iot-service:3000

# ── JWT (dari API Gateway) ───────────────────────────────────
JWT_SECRET=            # ← Sama dengan JWT_SECRET di root .env
```

> **Catatan:** Untuk development lokal (tanpa Docker), ubah `DB_HOST`, `RABBITMQ_HOST`, dan `NODEJS_IOT_SERVER_URL` ke `127.0.0.1` / `localhost`.

---

## 🚀 Menjalankan di Lokal (Docker Compose)

Cara termudah dan yang direkomendasikan adalah menggunakan Docker Compose yang akan menjalankan semua service sekaligus.

### Langkah 1: Clone & masuk ke direktori proyek

```bash
git clone <url-repo>
cd smart-city-platform
```

### Langkah 2: Setup semua file `.env`

```bash
# Root
cp .env.example .env

# API Gateway
cp express-gateway/.env.example express-gateway/.env

# PHP Service
cp php-service/.env.example php-service/.env
```

Isi masing-masing file sesuai panduan pada bagian [Setup Environment Variables](#️-setup-environment-variables) di atas.

### Langkah 3: Jalankan seluruh service

```bash
docker compose up -d --build
```

Proses build pertama membutuhkan waktu lebih lama (khususnya ML service yang men-training model secara otomatis). Pantau progressnya dengan:

```bash
docker compose logs -f
```

### Langkah 4: Verifikasi service berjalan

```bash
docker compose ps
```

Semua service harus berstatus `Up` atau `healthy`:

```
NAME                       STATUS
smartcity-mysql            Up (healthy)
smartcity-rabbitmq         Up (healthy)
smart-city-oauth-server    Up
smartcity-api-gateway      Up
smartcity-service          Up (healthy)
smartcity-iot-service      Up
smartcity-ml               Up (healthy)
smartcity-python-worker    Up
```

### Langkah 5: Akses layanan

| Layanan | URL |
|---|---|
| **API Gateway** (entry point utama) | http://localhost:3000 |
| **OAuth Server** | http://localhost:5001 |
| **PHP Service (Laravel)** | http://localhost:8000 |
| **IoT Service** | http://localhost:3001 |
| **ML Service** | http://localhost:5000 |
| **phpMyAdmin** | http://localhost:8081 |
| **RabbitMQ Management** | http://localhost:15672 (guest/guest) |
| **MySQL** | localhost:3307 (root/rootpass) |

---

### Perintah Docker berguna lainnya

```bash
# Hentikan semua service
docker compose down

# Hentikan dan hapus volume (data akan terhapus!)
docker compose down -v

# Rebuild satu service tertentu
docker compose up -d --build php-service

# Lihat log service tertentu
docker compose logs -f php-service
docker compose logs -f python-ml

# Masuk ke container
docker exec -it smartcity-service bash
docker exec -it smartcity-ml bash

# Jalankan artisan command
docker exec -it smartcity-service php artisan migrate
docker exec -it smartcity-service php artisan cache:clear
```

---

## 🖥️ Menjalankan di Server (Production)

### Opsi A: Docker Compose di VPS/Server

Cocok untuk deployment sederhana di satu server.

#### 1. Setup server

```bash
# Install Docker Engine
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Verifikasi
docker --version
docker compose version
```

#### 2. Clone repo & konfigurasi `.env`

```bash
git clone <url-repo>
cd smart-city-platform
```

Isi `.env` dengan konfigurasi **production**:

```env
# Root .env — Production
JWT_SECRET=              # Wajib diisi dengan secret yang kuat (min. 32 karakter)
MYSQL_ROOT_PASSWORD=     # Ganti dari default 'rootpass' dengan password kuat
MYSQL_DATABASE=smartcity
PHP_APP_KEY=             # Generate: php artisan key:generate --show

PHP_URL=https://your-domain.com
ML_URL=https://your-domain.com
OAUTH_URL=https://your-domain.com
IOT_URL=https://your-domain.com
```

Pada `php-service/.env`, ubah ke mode production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

#### 3. Jalankan

```bash
docker compose up -d --build
```

#### 4. Konfigurasi reverse proxy (Nginx)

Contoh konfigurasi Nginx untuk mengarahkan semua request ke API Gateway:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

Aktifkan HTTPS menggunakan Certbot:

```bash
sudo certbot --nginx -d your-domain.com
```

---

### Opsi B: Kubernetes (K8s)

Konfigurasi Kubernetes tersedia di direktori `k8s/`. Cocok untuk deployment skala besar dengan ketersediaan tinggi.

#### Prasyarat

- Cluster Kubernetes aktif (minikube, k3s, EKS, GKE, dll.)
- `kubectl` terinstal dan terkonfigurasi
- Docker image sudah di-push ke registry

#### Deploy ke Kubernetes

```bash
# 1. Buat namespace
kubectl apply -f k8s/namespace.yaml

# 2. Terapkan secrets (edit nilai sesuai kebutuhan sebelum apply!)
# PENTING: Edit k8s/secrets.yaml terlebih dahulu dengan nilai production
kubectl apply -f k8s/secrets.yaml

# 3. Terapkan ConfigMap
kubectl apply -f k8s/configmap.yaml

# 4. Deploy infrastruktur (MySQL & RabbitMQ)
kubectl apply -f k8s/mysql-statefulset.yaml
kubectl apply -f k8s/rabbitmq-deployment.yaml

# 5. Deploy semua service aplikasi
kubectl apply -f k8s/oauth-server-deployment.yaml
kubectl apply -f k8s/api-gateway-deployment.yaml
kubectl apply -f k8s/php-service-deployment.yaml
kubectl apply -f k8s/iot-service-deployment.yaml
kubectl apply -f k8s/python-ml-deployment.yaml
kubectl apply -f k8s/phpmyadmin-deployment.yaml

# 6. Terapkan Ingress
kubectl apply -f k8s/ingress.yaml

# Cek status semua pod
kubectl get pods -n smartcity
kubectl get services -n smartcity
```

> **Penting:** Sebelum apply `k8s/secrets.yaml` ke production, pastikan nilai default seperti `rootpass` dan `kelompok3` sudah diganti dengan nilai yang aman.

---

## 🧪 Testing API

Gunakan koleksi Postman yang tersedia di:

```
postman/SmartCityPlatform.postman_collection.json
```

Import file tersebut ke Postman, lalu set variabel environment:

```
base_url = http://localhost:3000
```

### Alur autentikasi dasar

```bash
# 1. Dapatkan token OAuth
curl -X POST http://localhost:5001/oauth/token \
  -H "Content-Type: application/json" \
  -d '{"grant_type":"password","username":"...","password":"..."}'

# 2. Gunakan token untuk request ke API Gateway
curl -X GET http://localhost:3000/php/api/... \
  -H "Authorization: Bearer <token>"
```

---

## 📁 Struktur Direktori

```
smart-city-platform/
├── .env.example              # ← Template env utama (Docker Compose)
├── docker-compose.yml        # ← Orkestrasi semua service
├── database/
│   └── schema.sql            # Skema database awal
├── express-gateway/          # API Gateway (Node.js/Express)
│   └── .env.example
├── oauth-server/             # OAuth2 Server (Node.js)
├── php-service/              # Backend utama (Laravel 11)
│   └── .env.example
├── iot/                      # IoT Service (Node.js + MQTT)
├── smart-city-ml-service/    # ML Service (Python/FastAPI)
│   └── training/             # Script training model ML
├── k8s/                      # Manifest Kubernetes
└── postman/                  # Koleksi Postman untuk testing
```

---

## 🔧 Troubleshooting

**Container PHP gagal start (waiting for database)**
Tunggu hingga MySQL sepenuhnya siap. Entrypoint secara otomatis akan retry hingga database terhubung.

**ML service lama saat build pertama**
Normal — proses build mencakup training tiga model ML (comfort, busy hour, anomaly). Bisa memakan waktu 2–5 menit.

**Port sudah digunakan**
Periksa apakah ada service lain yang memakai port `3000`, `5001`, `8000`, `3001`, `5000`, `3307`, `8081`, atau `15672`.
```bash
lsof -i :<nomor_port>
```

**Koneksi RabbitMQ gagal di ML Worker**
Pastikan RabbitMQ sudah `healthy` sebelum worker mencoba konek:
```bash
docker compose logs rabbitmq
docker compose restart python-ml-worker
```
