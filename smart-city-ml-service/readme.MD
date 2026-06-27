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
│   ├── smart_ml_dataset.csv      # Dataset utama (Suhu, Kebisingan, Waktu, dll)
│   ├── models/
│   │   ├── comfort_classifier.pkl # Model klasifikasi kenyamanan ruangan (Random Forest)
│   │   └── busy_hour_model.pkl     # Model tren jam sibuk (Random Forest Regressor)
│   └── anomaly_detector.pkl         # Model deteksi anomali sensor (Isolation Forest)
├── training/                     # SCRIPT TRAINING
│   ├── train_comfort_model.py       # Training menggunakan smart_ml_dataset.csv
│   ├── train_busy_hour_model.py     # Training menggunakan smart_ml_dataset.csv
│   └── train_anomaly_model.py       # Training menggunakan smart_ml_dataset.csv
├── main.py                         # Inisialisasi framework FastAPI
├── Dockerfile                    # Konfigurasi container untuk Docker
├── requirements.txt              # Dependensi Python (pandas, scikit-learn, librosa, dsb)
└── readme.MD                     # Dokumentasi servis ini

## Data Pipeline
Servis ini menggunakan dataset dunia nyata terpusat untuk akurasi prediksi dan sinkronisasi fitur yang lebih baik:

1. smart_ml_dataset.csv: Satu dataset utama yang mencakup fitur lingkungan terintegrasi (device_id, suhu, kelembaban, kebisingan, hour, is_weekend, timestamp, ml_classification_status). Digunakan serentak oleh ketiga skrip training untuk menjamin konsistensi pola data.

## Langkah Instalansi & Menjalankan Lokal

### Menjalankan dengan Docker (Rekomendasi)
Servis ini sudah dikonfigurasi untuk berjalan mulus bersama servis SmartCity lainnya (Laravel, MySQL, dll) menggunakan Docker.
1. Buka terminal dan pastikan berada di root folder proyek utama (misal: `smart-city-platform/`)
2. Jalankan perintah eksekusi Docker:
``` text
docker compose up -d --build python-ml

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
- Input: 5 parameter (temperature_c, humidity_pct, decibel_level, hour, is_weekend).
3. Prediksi Jam Sibuk: POST /api/v1/predict-busy-hour
- Input: 4 parameter (room_id, temperature_c, humidity_pct, decibel_level).
- Output: Mengembalikan prediksi jam sibuk dalam angka format 24 jam serta pesan dinamis berbasis waktu lokal Jakarta (WIB) mengenai estimasi sisa waktu kesibukan (hari ini/besok).
4. Deteksi Anomali Sensor: POST /api/v1/detect-anomaly
- Input: 4 parameter (temperature_c, humidity_pct, decibel_level, hour).
- Output: Status boolean is_anomaly (true/false) untuk memvalidasi kesehatan data sensor dari kerusakan atau nilai ekstrem.

### Menjalankan Server API ML
``` uvicorn app.main:app --reload --port 5000