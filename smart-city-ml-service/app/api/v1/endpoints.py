import asyncio
import json
import os
import httpx
import joblib
import pandas as pd
import numpy as np  
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel

router = APIRouter()

# URL Callback
PHP_CALLBACK_URL = os.getenv("PHP_CALLBACK_URL", "http://php-service:8000/api/telemetry/callback")

model_rf = None
label_encoder = None
busy_hour_model = None
anomaly_detector = None

class ComfortAnalyzeRequest(BaseModel):
    temperature_c: float
    humidity_pct: float
    decibel_level: float = 45.0
    hour: int = 12
    is_weekend: int = 0

class BusyHourRequest(BaseModel):
    room_id: int
    temperature_c: float
    humidity_pct: float
    decibel_level: float

class AnomalyRequest(BaseModel):
    temperature_c: float
    humidity_pct: float
    decibel_level: float
    hour: int

def load_models():
    global model_rf, label_encoder, busy_hour_model, anomaly_detector
    
    if model_rf is None or label_encoder is None:  
        try:
            from app.core.model_loader import load_comfort_model
            saved_data = load_comfort_model()
            model_rf = saved_data['model']
            label_encoder = saved_data['encoder']
            print(f"[Consumer] ✓ Berhasil memuat model comfort_classifier.pkl ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat model/encoder kamu: {e}")
            raise

    # Memuat Model Jam Sibuk
    if busy_hour_model is None:
        try:  
            from app.core.model_loader import load_busy_hour_model
            busy_hour_model = load_busy_hour_model()
            print(f"[Consumer] ✓ Berhasil memuat busy_hour_model milik teman ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat busy_hour_model milik teman: {e}")
            
    # Memuat Model Anomali
    if anomaly_detector is None:
        try:
            from app.core.model_loader import get_model_path
            path = get_model_path('anomaly_detector.pkl')
            anomaly_detector = joblib.load(path)
            print(f"[Consumer] ✓ Berhasil memuat anomaly_detector.pkl ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat anomaly_detector.pkl: {e}")

@router.post("/analyze-comfort")
async def analyze_comfort_api(req: ComfortAnalyzeRequest):
    try:
        load_models()
        
        features = pd.DataFrame([{
            'suhu': req.temperature_c,
            'kelembaban': req.humidity_pct,
            'kebisingan': req.decibel_level,
            'hour': req.hour,
            'is_weekend': req.is_weekend
        }])
        
        pred_encoded = model_rf.predict(features)[0]
        status = label_encoder.inverse_transform([pred_encoded])[0]
        
        return {
            "status": "success",
            "comfort_status": status,
            "input_data": req.model_dump()
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/predict-busy-hour")
async def predict_busy_hour_api(req: BusyHourRequest):
    try:
        load_models()
        
        features = np.array([[req.temperature_c, req.humidity_pct, req.decibel_level]])
        predicted_hour = int(busy_hour_model.predict(features)[0])
        
        return {
            "status": "success",
            "room_id": req.room_id,
            "predicted_next_busy_hour": predicted_hour
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/detect-anomaly")
async def detect_anomaly_api(req: AnomalyRequest):
    try:
        load_models()
        
        features = np.array([[req.temperature_c, req.humidity_pct, req.decibel_level, req.hour]])
        # IsolationForest: -1 = anomali, 1 = normal
        is_anomaly = int(anomaly_detector.predict(features)[0]) == -1
        
        return {
            "status": "success",
            "is_anomaly": is_anomaly
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))