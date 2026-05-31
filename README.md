# 🏙️ Smart City Platform — Panduan Kontribusi

Panduan ini membantu anggota tim untuk clone, setup, branching, dan menjalankan service yang sudah tersedia (**Express Gateway** dan **IoT Simulator**).

---

## 📋 Prerequisites

Pastikan sudah terinstall di laptop kamu:

| Tools          | Versi Minimum | Cek                |
| -------------- | ------------- | ------------------ |
| Git            | 2.x           | `git --version`    |
| Docker Desktop | 4.x           | `docker --version` |
| Node.js        | 18.x          | `node --version`   |
| Python         | 3.11.x        | `python --version` |

> **Windows users:** Gunakan **Git Bash** untuk semua command di panduan ini.

---

## 🚀 Step 1 — Clone Repository

```bash
git clone https://github.com/<username>/smart-city-platform.git
cd smart-city-platform
```

---

## 🌿 Step 2 — Branching

Semua kontribusi harus dari branch `main` dengan nama branch `feature/<service-yang-dikerjakan>`.

```bash
# Pastikan di branch main dan up to date
git checkout main
git pull origin main

# Buat branch baru sesuai service yang dikerjakan
git checkout -b feature/<nama-service>
```

**Contoh nama branch:**

```bash
git checkout -b feature/express-gateway
git checkout -b feature/iot-simulator
git checkout -b feature/php-citizen
git checkout -b feature/php-traffic
git checkout -b feature/php-environment
git checkout -b feature/python-ml
git checkout -b feature/oauth-server
```

> ⚠️ **Jangan langsung push ke `main`**. Selalu buat Pull Request dari branch kamu ke `main`.

---

## 📁 Step 3 — Setup File yang Diperlukan

Buat folder dan file yang tidak ter-commit di Git (karena ada di `.gitignore`):

```bash
# Buat folder untuk Mosquitto
mkdir -p iot/data iot/log

# Buat file passwd kosong untuk Mosquitto
printf '' > iot/passwd
```

Buat file `.env` dari template:

```bash
cp express-gateway/.env.example express-gateway/.env
cp iot/.env.example iot/.env
```

> ⚠️ Jangan pernah commit file `.env` ke Git!

---

## 🐳 Step 4 — Jalankan Service via Docker

### 4.1 — Jalankan Mosquitto + Express Gateway + IoT Simulator sekaligus

```bash
MSYS_NO_PATHCONV=1 docker compose up -d --build mosquitto api-gateway iot-simulator
```

### 4.2 — Cek semua container berjalan

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

Output yang diharapkan:

```
NAMES                              STATUS          PORTS
smart-city-platform-mosquitto-1    Up x seconds    0.0.0.0:1883->1883/tcp
smart-city-platform-api-gateway-1  Up x seconds    0.0.0.0:3000->3000/tcp
smart-city-platform-iot-simulator  Up x seconds
```

### 4.3 — Buat user MQTT (wajib dilakukan sekali)

Cek nama container mosquitto:

```bash
docker ps --format "table {{.Names}}\t{{.Status}}" | grep mosquitto
```

Lalu buat user:

```bash
MSYS_NO_PATHCONV=1 docker exec -it <nama-container-mosquitto> sh -c "
mosquitto_passwd -b /mosquitto/config/passwd iot_device iot_secret &&
mosquitto_passwd -b /mosquitto/config/passwd nodered nodered_secret &&
mosquitto_passwd -b /mosquitto/config/passwd mqtt_admin admin_secret
"
```

### 4.4 — Restart IoT Simulator

Setelah user MQTT dibuat, restart simulator agar bisa connect:

```bash
docker compose restart iot-simulator
```

---

## ✅ Step 5 — Verifikasi Semua Berjalan

### Cek Express Gateway

```bash
curl http://localhost:3000/health/gateway
```

Response yang diharapkan:

```json
{
  "status": "success",
  "code": 200,
  "data": {
    "gateway": "healthy",
    "uptime_seconds": 10,
    "memory_mb": 45
  },
  "message": "Gateway sehat",
  "service": "api-gateway"
}
```

### Cek IoT Simulator

```bash
docker logs smart-city-platform-iot-simulator --tail 20
```

Output yang diharapkan (setiap 30 detik):

```
2026-05-31 12:40:50 | INFO | [zone1/traffic] density= 20.8 speed= 60.0 km/h incident=0
2026-05-31 12:40:50 | INFO | [zone1/air    ] PM2.5= 28.93 PM10= 43.49 Temp= 32.1°C
...
2026-05-31 12:40:50 | INFO | Cycle selesai — 10/10 berhasil | total published: 10
```

### Cek data MQTT masuk ke broker

```bash
MSYS_NO_PATHCONV=1 docker exec -it <nama-container-mosquitto> sh -c "
mosquitto_sub -h localhost -p 1883 -u mqtt_admin -P admin_secret -t 'city/#' -v
"
```

Data akan muncul setiap 30 detik:

```
city/zone1/traffic {"zone":"zone1","vehicle_density":20.8,...}
city/zone1/air {"zone":"zone1","pm25":28.93,...}
```

---

## 🔄 Step 6 — Workflow Harian

### Sebelum mulai coding

```bash
# Update branch main
git checkout main
git pull origin main

# Update branch kamu
git checkout feature/<nama-service>
git merge main
```

### Setelah selesai coding

```bash
# Tambahkan perubahan
git add .

# Commit dengan pesan yang jelas
git commit -m "feat: <deskripsi singkat perubahan>"

# Push ke remote
git push origin feature/<nama-service>
```

Lalu buat **Pull Request** di GitHub dari branch kamu ke `main`.

---

## 🛑 Troubleshooting

### Mosquitto tidak bisa start

```bash
# Pastikan file passwd ada
ls -la iot/passwd

# Kalau tidak ada, buat ulang
printf '' > iot/passwd
docker compose restart mosquitto
```

### IoT Simulator rc=4 (auth error)

```bash
# Berarti user MQTT belum dibuat, ulangi Step 4.3
```

### Container nama berbeda

```bash
# Cek nama container yang benar
docker ps --format "table {{.Names}}\t{{.Status}}"
```

### Port 3000 sudah dipakai

```bash
# Cek proses yang pakai port 3000
netstat -ano | grep 3000

# Atau ganti port di docker-compose.yml
ports:
  - "3001:3000"  # ubah 3000 ke port lain
```

### Reset semua dari awal

```bash
# Stop dan hapus semua container
docker compose down

# Hapus volume (hati-hati: data hilang)
docker compose down -v

# Buat ulang dari Step 3
```

---

## 📌 Catatan Penting

- `iot/passwd` — **tidak di-commit**, buat manual di setiap laptop
- `iot/data/` — **tidak di-commit**, data runtime Mosquitto
- `.env` — **tidak di-commit**, berisi secret
- `*.pkl` — **tidak di-commit**, file model ML terlalu besar
- Selalu `git pull` sebelum mulai kerja untuk hindari konflik
