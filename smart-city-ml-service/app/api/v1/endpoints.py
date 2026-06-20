from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List
import numpy as np
import librosa
from app.core.model_loader import load_comfort_model, load_busy_hour_model

router = APIRouter()

comfort_model = load_comfort_model()
busy_hour_model = load_busy_hour_model()

# --- SCHEMAS ---
class TelemetryRequest(BaseModel):
    audio_file_path: str 

class BusyHourRequest(BaseModel):
    room_id: int
    # Data historis tingkat kebisingan (decibel) untuk diproses oleh model
    historical_decibels: List[float]

# --- ENDPOINTS ---
@router.post("/analyze-telemetry")
async def analyze_telemetry(request: TelemetryRequest):
    try:
        y, sr = librosa.load(request.audio_file_path)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=40)
        mfccs_scaled = np.mean(mfccs.T, axis=0).reshape(1, -1)
        
        prediction = int(comfort_model.predict(mfccs_scaled)[0])
        status_kenyamanan = "Bising/Ramai" if prediction == 1 else "Sepi/Normal"
        
        return {
            "status": "success",
            "class_result": prediction,
            "comfort_status": status_kenyamanan
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/predict-busy-hour")
async def predict_busy_hour(request: BusyHourRequest):
    try:
        if not request.historical_decibels:
            raise HTTPException(status_code=400, detail="Array historical_decibels tidak boleh kosong")

        # Jika model asli belum ada, kita gunakan logika dummy (mengambil jam dengan desibel tertinggi + 1)
        # Ini hanya agar frontend/PHP bisa dites sebelum model AI selesai di-training
        if busy_hour_model is None:
            # Simulasi sederhana: prediksi jam sibuk adalah index dari nilai desibel tertinggi
            dummy_predicted_hour = int(np.argmax(request.historical_decibels))
            return {
                "status": "success",
                "room_id": request.room_id,
                "predicted_busy_hour": dummy_predicted_hour,
                "note": "Menggunakan dummy logic karena file .pkl belum ditemukan"
            }

        # Logika jika model .pkl sudah ada
        # Reshape data menjadi 2D array: 1 baris, n kolom (sesuai format scikit-learn)
        features = np.array(request.historical_decibels).reshape(1, -1)
        
        prediction = int(busy_hour_model.predict(features)[0])
        
        return {
            "status": "success",
            "room_id": request.room_id,
            "predicted_busy_hour": prediction
        }
        
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))