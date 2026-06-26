# 🧠 SmartCity Machine Learning & Analytics Service

Servis ini merupakan komponen kecerdasan buatan berbasis Python di dalam ekosistem SmartCity. Berfungsi untuk melakukan komputasi analitik penyerapan telemetri secara real-time dan mengklasifikasikan tingkat kenyamanan kebisingan ruangan menggunakan model Machine Learning yang telah dilatih.

## 📁 Struktur Direktori Servis
```text
smart-city-ml-service/
├── app/
│   ├── api/
│   │   └── v1/
│   │       └── endpoints.py      # Definisi route prediksi internal API
│   ├── core/
│   │   ├── config.py             # Pembacaan environment variabel & kredensial
│   │   └── model_loader.py       # Pemuat berkas model prediksi (.pkl)
├── data/                         # PENYIMPANAN DATASET (CSV)
│   ├── room_occupancy.csv        # Dataset okupansi & sensor
│   └── urban_noise_levels.csv    # Dataset kebisingan kota
│   ├── models/
│   │   ├── comfort_classifier.pkl # Model klasifikasi kenyamanan ruangan (Random Forest)
│   │   └── busy_hour_forecaster.pkl # Model forecasting tren jam sibuk (Placeholder)
├── training/                     # SCRIPT TRAINING
│   ├── train_comfort_model.py    # Training menggunakan urban_noise_levels.csv
│   └── train_busy_hour_model.py  # Training menggunakan room_occupancy.csv
│   └── main.py                   # Inisialisasi framework FastAPI
├── Dockerfile                    # Konfigurasi container untuk Docker
├── requirements.txt              # Dependensi Python (pandas, scikit-learn, librosa, dsb)
└── readme.MD                     # Dokumentasi servis ini

## Data Pipeline
Servis ini kini menggunakan dataset dunia nyata untuk akurasi prediksi yang lebih baik:
1. Urban Noise Levels: Digunakan untuk mengklasifikasikan status kebisingan ruangan berdasarkan fitur lingkungan (suhu, kelembapan, dsb).
2. Room Occupancy: Digunakan untuk memprediksi jam sibuk ruangan berdasarkan data sensor (PIR, CO2, Cahaya, Suhu).

## Langkah Instalansi & Menjalankan Lokal

### Menjalankan dengan Docker (Rekomendasi)
Servis ini sudah dikonfigurasi untuk berjalan mulus bersama servis SmartCity lainnya (Laravel, MySQL, dll) menggunakan Docker.
1. Buka terminal dan pastikan berada di root folder proyek utama (misal: `smart-city-platform/`)
2. Jalankan perintah eksekusi Docker:
``` text
docker-compose up -d --build

3. Servis ML akan menyala di dalam container bernama smartcity-ml.

### Membuat Virtual Environment
```text
python -m venv venv
# Aktivasi di Windows (PowerShell):
.\venv\Scripts\Activate.ps1
# Aktivasi di Mac/Linux:
source venv/bin/activate

### Instalasi Dependensi Pustaka
```text
pip install -r requirements.txt

### Integrasi API
1. Base URL: `http://localhost:5000`
2. Klasifikasi Kenyamanan: POST /api/v1/analyze-comfort
Input: 8 parameter lingkungan (suhu, kelembapan, dll).
3. Prediksi Jam Sibuk: POST /api/v1/predict-busy-hour
Input: 5 parameter sensor (suhu, cahaya, suara, CO2, PIR).

### Menjalankan Server API ML
``` uvicorn app.main:app --reload --port 5000