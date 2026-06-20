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
│   ├── models/
│   │   ├── comfort_classifier.pkl # Model klasifikasi kenyamanan ruangan (Random Forest)
│   │   └── busy_hour_forecaster.pkl # Model forecasting tren jam sibuk (Placeholder)
│   └── main.py                   # Inisialisasi framework FastAPI
├── requirements.txt              # Dependensi Python (pandas, scikit-learn, librosa, dsb)
└── readme.MD                     # Dokumentasi servis ini

## Langkah Instalansi & Menjalankan Lokal

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


### Menjalankan Server API ML
``` uvicorn app.main:app --reload --port 5000