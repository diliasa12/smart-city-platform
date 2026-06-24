from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field
import numpy as np
import librosa
from app.core.model_loader import load_comfort_model, load_busy_hour_model

router = APIRouter()

comfort_model = load_comfort_model()
busy_hour_model = load_busy_hour_model()

# --- SCHEMAS ---
class TelemetryPayload(BaseModel):
    """
    Schema menerima payload dari RabbitMQ / Laravel. 
    """
    log_id: int = Field(alias="log_id", default=0)
    temperature: float
    humidity: float
    decibel_level: float
    
    # Fitur untuk Comfort Model 
    near_construction: int = Field(default=0)
    population_density: float = Field(default=500.0)
    public_event: int = Field(default=0)
    school_zone: int = Field(default=1)

    # Fitur untuk Busy Hour Model
    light: float = Field(default=300.0)
    co2: float = Field(default=400.0)
    pir: int = Field(default=1)

class TelemetryRequest(BaseModel):
    audio_file_path: str 


# --- ENDPOINTS ---

@router.post("/process-telemetry")
async def process_telemetry(payload: TelemetryPayload):
    """
    Endpoint terpadu untuk memproses data dari RabbitMQ.
    Menghasilkan klasifikasi kenyamanan dan prediksi jam sibuk sekaligus.
    """
    try:
        # Prediksi Kenyamanan 
        comfort_features = np.array([[
            payload.temperature, 
            payload.humidity, 
            payload.decibel_level, 
            payload.near_construction,
            payload.population_density, 
            payload.public_event, 
            payload.school_zone
        ]])
        
        comfort_pred = int(comfort_model.predict(comfort_features)[0])
        
        if comfort_pred == 0:
            status_kenyamanan = "nyaman"
        elif comfort_pred == 1:
            status_kenyamanan = "tidak_nyaman"
        else:
            status_kenyamanan = "cukup_nyaman"
            
        # Prediksi Jam Sibuk
        busy_features = np.array([[
            payload.temperature, 
            payload.light, 
            payload.decibel_level, 
            payload.co2, 
            payload.pir
        ]])
        
        busy_pred = int(busy_hour_model.predict(busy_features)[0])
        
        # Format Output untuk Laravel
        return {
            "telemetry_log_id": payload.log_id,
            "ml_classification_status": status_kenyamanan,
            "predicted_next_busy_hour": busy_pred
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/analyze-telemetry-audio")
async def analyze_telemetry_audio(request: TelemetryRequest):
    """Tetap ada untuk pemrosesan file audio mentah jika sewaktu-waktu dibutuhkan"""
    try:
        y, sr = librosa.load(request.audio_file_path)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=40)
        mfccs_scaled = np.mean(mfccs.T, axis=0).reshape(1, -1)
        
        prediction = int(comfort_model.predict(mfccs_scaled)[0])
        
        if prediction == 0:
            status_kenyamanan = "nyaman"
        elif prediction == 1:
            status_kenyamanan = "tidak_nyaman"
        else:
            status_kenyamanan = "cukup_nyaman"
            
        return {
            "status": "success", 
            "ml_classification_status": status_kenyamanan
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))