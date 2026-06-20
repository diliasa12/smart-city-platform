import os
import joblib

MODEL_PATH = os.path.join(os.path.dirname(__file__), '..', 'models', 'comfort_classifier.pkl')

def load_comfort_model():
    if not os.path.exists(MODEL_PATH):
        raise FileNotFoundError(f"Berkas model tidak ditemukan di: {MODEL_PATH}")
    
    # load model Random Forest
    model = joblib.load(MODEL_PATH)
    return model