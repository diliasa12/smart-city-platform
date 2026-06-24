import joblib
import os

def get_model_path(filename):
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    return os.path.join(base_dir, 'models', filename)

def load_comfort_model():
    path = get_model_path('comfort_classifier.pkl')
    if not os.path.exists(path):
        raise FileNotFoundError(f"Berkas model tidak ditemukan di: {path}")
    return joblib.load(path)

def load_busy_hour_model():
    path = get_model_path('busy_hour_forecaster.pkl')
    if not os.path.exists(path):
        raise FileNotFoundError(f"Berkas model tidak ditemukan di: {path}")
    return joblib.load(path)