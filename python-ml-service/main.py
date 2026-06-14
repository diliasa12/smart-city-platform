"""
Smart City ML Service - FastAPI Server
REST API untuk prediksi traffic, klasifikasi air quality, dan anomaly detection.

Features:
- Startup load: Load models bundle sekali saja saat server start
- Health check endpoint
- Traffic density predictor endpoint
- Standardized JSON response format
- Error handling dengan HTTPException

Usage:
    uvicorn main:app --host 0.0.0.0 --port 5000 --reload

Endpoints:
    GET  /health - Service health status
    POST /predict/traffic - Traffic density prediction
"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import joblib
import numpy as np
from datetime import datetime
import logging
from contextlib import asynccontextmanager
import os

# ============================================================================
# LOGGING SETUP
# ============================================================================

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# ============================================================================
# GLOBAL VARIABLES
# ============================================================================

BUNDLE = None  # Will be loaded on startup
BUNDLE_PATH = "models/smartcity_models.pkl"

# ============================================================================
# STARTUP / SHUTDOWN EVENTS
# ============================================================================

@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Lifespan context manager untuk loading models pada startup dan cleanup pada shutdown.
    FastAPI 0.93+ uses lifespan context manager instead of @app.on_event.
    """
    # STARTUP
    logger.info("=" * 80)
    logger.info("SMART CITY ML SERVICE - STARTUP")
    logger.info("=" * 80)
    
    global BUNDLE
    
    try:
        if not os.path.exists(BUNDLE_PATH):
            raise FileNotFoundError(f"Bundle file not found: {BUNDLE_PATH}")
        
        logger.info(f"Loading models bundle from: {BUNDLE_PATH}")
        BUNDLE = joblib.load(BUNDLE_PATH)
        
        logger.info("✓ Bundle loaded successfully")
        logger.info(f"  Models included:")
        logger.info(f"    - traffic: RandomForest + scaler + location_encoder")
        logger.info(f"    - air: GradientBoosting + scaler + aqi_encoder")
        logger.info(f"    - anomaly: IsolationForest + scaler")
        logger.info("=" * 80)
        logger.info("Server is ready to accept requests!")
        logger.info("=" * 80)
        
    except Exception as e:
        logger.error(f"✗ Failed to load bundle: {str(e)}")
        raise RuntimeError(f"Cannot start service without models: {str(e)}")
    
    yield  # Server running here
    
    # SHUTDOWN
    logger.info("Shutting down service...")
    logger.info("Goodbye!")


app = FastAPI(
    title="Smart City ML Service",
    description="REST API untuk prediksi traffic, klasifikasi air quality, dan anomaly detection",
    version="1.0.0",
    lifespan=lifespan
)

# ============================================================================
# PYDANTIC SCHEMAS
# ============================================================================

class TrafficIn(BaseModel):
    """
    Input schema untuk traffic density prediction.
    
    Fields:
        - hour: Jam dalam sehari (0-23)
        - day_of_week: Hari dalam seminggu (0-6, 0=Monday)
        - weather_code: Kode cuaca (0=Cerah, 1=Mendung, 2=Hujan, 3=Badai)
        - prev_density: Kepadatan sebelumnya (0-100)
        - location: Nama lokasi (misal: 'Sudirman', 'Thamrin', dll)
    """
    hour: int = Field(..., ge=0, le=23, description="Hour of day (0-23)")
    day_of_week: int = Field(..., ge=0, le=6, description="Day of week (0-6)")
    weather_code: int = Field(..., ge=0, le=3, description="Weather code (0-3)")
    prev_density: float = Field(..., ge=0.0, description="Previous density (0+)")
    location: str = Field(..., description="Location name (e.g., 'Sudirman', 'Thamrin')")
    
    class Config:
        json_schema_extra = {
            "example": {
                "hour": 8,
                "day_of_week": 1,
                "weather_code": 0,
                "prev_density": 45.5,
                "location": "Sudirman"
            }
        }


class HealthResponse(BaseModel):
    """Health check response schema."""
    status: str
    message: str
    timestamp: str


class TrafficPredictionResponse(BaseModel):
    """Traffic prediction response schema."""
    status: str
    code: int
    data: dict
    message: str
    timestamp: str
    service: str


# ============================================================================
# UTILITY FUNCTIONS
# ============================================================================

def get_current_timestamp() -> str:
    """Get current timestamp in ISO 8601 format."""
    return datetime.utcnow().isoformat() + "Z"


def determine_congestion_level(density: float) -> str:
    """
    Determine traffic congestion level based on predicted density.
    
    Args:
        density: Predicted vehicle density (0-100)
    
    Returns:
        Congestion level string: "Padat" (heavy), "Sedang" (medium), "Lancar" (light)
    """
    if density > 80:
        return "Padat"
    elif density > 40:
        return "Sedang"
    else:
        return "Lancar"


def create_response(status: str, code: int, data: dict, message: str, service: str = "python-ml") -> dict:
    """
    Create standardized JSON response.
    
    Args:
        status: Response status ("success" or "error")
        code: HTTP status code
        data: Response data payload
        message: Human-readable message
        service: Service identifier
    
    Returns:
        Standardized response dict
    """
    return {
        "status": status,
        "code": code,
        "data": data,
        "message": message,
        "timestamp": get_current_timestamp(),
        "service": service
    }


# ============================================================================
# ENDPOINTS
# ============================================================================

@app.get("/health", response_model=HealthResponse, tags=["Health"])
async def health_check():
    """
    Health check endpoint.
    
    Returns:
        - status: "healthy"
        - message: Service status
        - timestamp: Current timestamp
    """
    try:
        # Check if bundle is loaded
        if BUNDLE is None:
            raise Exception("Models bundle not loaded")
        
        # Verify bundle structure
        required_keys = ['traffic', 'air', 'anomaly']
        for key in required_keys:
            if key not in BUNDLE:
                raise Exception(f"Missing model: {key}")
        
        return HealthResponse(
            status="healthy",
            message="Service is running and all models are loaded",
            timestamp=get_current_timestamp()
        )
    
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}")
        raise HTTPException(
            status_code=503,
            detail=f"Service unavailable: {str(e)}"
        )


@app.post("/predict/traffic", response_model=TrafficPredictionResponse, tags=["Predictions"])
async def predict_traffic(input_data: TrafficIn) -> dict:
    """
    Traffic density prediction endpoint.
    
    Args:
        input_data: TrafficIn schema dengan hour, day_of_week, weather_code, prev_density, location
    
    Returns:
        Standardized response dengan predicted_density dan congestion_level
    
    Raises:
        HTTPException: Jika terjadi error selama prediksi (500 Internal Server Error)
    """
    try:
        # Validate bundle loaded
        if BUNDLE is None:
            raise Exception("Models bundle not loaded")
        
        if 'traffic' not in BUNDLE:
            raise Exception("Traffic model not found in bundle")
        
        # Extract components from bundle
        traffic_bundle = BUNDLE['traffic']
        model = traffic_bundle['model']
        scaler = traffic_bundle['scaler']
        le_location = traffic_bundle['le_loc']
        features = traffic_bundle['features']
        
        logger.info(f"Received traffic prediction request: {input_data.dict()}")
        
        # ===== DATA PREPARATION =====
        
        # Encode location menggunakan LabelEncoder dari model
        try:
            location_encoded = le_location.transform([input_data.location])[0]
            logger.info(f"Location '{input_data.location}' encoded as: {location_encoded}")
        except ValueError as e:
            logger.error(f"Location encoding error: {str(e)}")
            raise HTTPException(
                status_code=400,
                detail=f"Location '{input_data.location}' not recognized. Available: {list(le_location.classes_)}"
            )
        
        # Prepare feature array sesuai urutan features di model
        feature_dict = {
            'hour': input_data.hour,
            'day_of_week': input_data.day_of_week,
            'weather_code': input_data.weather_code,
            'prev_density': input_data.prev_density,
            'location_enc': location_encoded
        }
        
        # Create feature vector dalam urutan yang benar
        X = np.array([[feature_dict[feat] for feat in features]])
        logger.info(f"Features before scaling: {X}")
        
        # ===== SCALING =====
        
        X_scaled = scaler.transform(X)
        logger.info(f"Features after scaling: {X_scaled}")
        
        # ===== PREDICTION =====
        
        predicted_density = model.predict(X_scaled)[0]
        predicted_density = round(float(predicted_density), 1)  # Round to 1 decimal
        
        logger.info(f"Predicted density: {predicted_density}")
        
        # ===== BUSINESS LOGIC =====
        
        congestion_level = determine_congestion_level(predicted_density)
        logger.info(f"Congestion level: {congestion_level}")
        
        # ===== RESPONSE =====
        
        response_data = {
            "predicted_density": predicted_density,
            "congestion_level": congestion_level
        }
        
        response = create_response(
            status="success",
            code=200,
            data=response_data,
            message="Traffic prediction processed successfully",
            service="python-ml"
        )
        
        logger.info(f"Response: {response}")
        return response
    
    except HTTPException:
        # Re-raise HTTPException as-is
        raise
    
    except Exception as e:
        logger.error(f"Error during traffic prediction: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Internal server error during prediction: {str(e)}"
        )


# ============================================================================
# ROOT ENDPOINT
# ============================================================================

@app.get("/", tags=["Info"])
async def root():
    """Root endpoint dengan informasi service."""
    return {
        "service": "Smart City ML Service",
        "version": "1.0.0",
        "status": "online",
        "endpoints": {
            "health": "GET /health",
            "traffic_prediction": "POST /predict/traffic",
            "api_docs": "GET /docs (Swagger UI)",
            "api_redoc": "GET /redoc (ReDoc UI)"
        }
    }


# ============================================================================
# ERROR HANDLERS
# ============================================================================

@app.exception_handler(HTTPException)
async def http_exception_handler(request, exc):
    """Handle HTTP exceptions dengan standardized format."""
    return {
        "status": "error",
        "code": exc.status_code,
        "data": {},
        "message": exc.detail,
        "timestamp": get_current_timestamp(),
        "service": "python-ml"
    }


# ============================================================================
# MAIN
# ============================================================================

if __name__ == "__main__":
    import uvicorn
    
    logger.info("Starting FastAPI server...")
    uvicorn.run(
        app,
        host="0.0.0.0",
        port=5000,
        log_level="info"
    )
