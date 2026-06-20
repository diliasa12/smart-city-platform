from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
import numpy as np
import librosa
from app.core.model_loader import load_comfort_model, load_busy_hour_model

router = APIRouter()

comfort_model = load_comfort_model()
busy_hour_model = load_busy_hour_model()

# --- SCHEMAS ---
class TelemetryRequest(BaseModel):
    audio_file_path: str 

class NoiseRequest(BaseModel):
    # 15 fitur lingkungan dari dataset urban_noise_levels.csv
    temperature_c: float
    humidity_pct: float
    wind_speed_kmh: float
    precipitation_mm: float
    traffic_density: float
    near_airport: int
    near_highway: int
    near_construction: int
    population_density: float
    park_proximity: int
    industrial_zone: int
    vehicle_count: int
    honking_events: int
    public_event: int
    holiday: int
    school_zone: int

# --- ENDPOINTS ---

@router.post("/analyze-comfort")
async def analyze_comfort(request: NoiseRequest):
    try:
        # Menyusun fitur sesuai urutan saat training
        features = np.array([[
            request.temperature_c, request.humidity_pct, request.wind_speed_kmh, request.precipitation_mm,
            request.traffic_density, request.near_airport, request.near_highway, request.near_construction,
            request.population_density, request.park_proximity, request.industrial_zone, request.vehicle_count,
            request.honking_events, request.public_event, request.holiday, request.school_zone
        ]])
        
        prediction = int(comfort_model.predict(features)[0])
        status_kenyamanan = "Bising/Ramai" if prediction == 1 else "Sepi/Normal"
        
        return {
            "status": "success",
            "comfort_status": status_kenyamanan,
            "raw_prediction": prediction
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/analyze-telemetry")
async def analyze_telemetry(request: TelemetryRequest):
    """Tetap ada untuk pemrosesan file audio mentah"""
    try:
        y, sr = librosa.load(request.audio_file_path)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=40)
        mfccs_scaled = np.mean(mfccs.T, axis=0).reshape(1, -1)
        
        prediction = int(comfort_model.predict(mfccs_scaled)[0])
        return {"status": "success", "comfort_status": "Bising" if prediction == 1 else "Sepi"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))