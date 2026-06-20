import os
import joblib

COMFORT_MODEL_PATH = os.path.join(os.path.dirname(__file__), '..', 'models', 'comfort_classifier.pkl')
BUSY_HOUR_MODEL_PATH = os.path.join(os.path.dirname(__file__), '..', 'models', 'busy_hour_forecaster.pkl')

def load_comfort_model():
    if not os.path.exists(COMFORT_MODEL_PATH):
        raise FileNotFoundError(f"Berkas model tidak ditemukan di: {COMFORT_MODEL_PATH}")
    return joblib.load(COMFORT_MODEL_PATH)

def load_busy_hour_model():
    # Mengembalikan None jika file belum ada agar API tidak crash saat startup
    if not os.path.exists(BUSY_HOUR_MODEL_PATH):
        print(f"[Warning] Model {BUSY_HOUR_MODEL_PATH} belum ada. Menggunakan mode dummy.")
        return None
    return joblib.load(BUSY_HOUR_MODEL_PATH)