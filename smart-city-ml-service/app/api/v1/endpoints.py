from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
import numpy as np
import librosa
from app.core.model_loader import load_comfort_model

router = APIRouter()

model = load_comfort_model()

class TelemetryRequest(BaseModel):
    audio_file_path: str 

@router.post("/analyze-telemetry")
async def analyze_telemetry(request: TelemetryRequest):
    try:
        y, sr = librosa.load(request.audio_file_path)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=40)
        mfccs_scaled = np.mean(mfccs.T, axis=0).reshape(1, -1) # Reshape ke 2D untuk single sample prediction
        
        # (0 = Sepi/Normal, 1 = Bising/Crowd)
        prediction = int(model.predict(mfccs_scaled)[0])
        
        status_kenyamanan = "Bising/Ramai" if prediction == 1 else "Sepi/Normal"
        
        return {
            "status": "success",
            "class_result": prediction,
            "comfort_status": status_kenyamanan
        }
        
    except Exception as e:
        raise HTTPException(status_status=500, detail=str(e))