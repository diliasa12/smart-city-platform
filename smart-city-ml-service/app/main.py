import os
from contextlib import asynccontextmanager
from dotenv import load_dotenv

load_dotenv()

from fastapi import FastAPI
from app.api.v1.seat_recommendation import router as seat_router
from app.core.model_loader import load_comfort_model

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Load model saja, tidak perlu RabbitMQ consumer
    print("[ML Service] Memuat model ML ke memori...")
    app.state.comfort_model = load_comfort_model()
    print("[ML Service] ✓ Model berhasil dimuat.")
    print("[ML Service] ✓ Server siap.\n")

    yield

    del app.state.comfort_model
    print("[ML Service] ✓ Resource dibersihkan. Server berhenti.")


app = FastAPI(
    title="SmartCity ML & Analytics Service",
    description="Layanan AI untuk rekomendasi dan klasifikasi kenyamanan.",
    version="2.1.0",
    lifespan=lifespan,
)

app.include_router(seat_router, prefix="/api/v1", tags=["Seat Recommendation"])

@app.get("/", tags=["Root"])
def read_root():
    return {"message": "Smart City ML Service is running!"}

@app.get("/health", tags=["Health Check"])
async def health_check():
    model_loaded = hasattr(app.state, "comfort_model")
    return {
        "status": "ok" if model_loaded else "degraded",
        "service": "smartcity-ml-service",
        "model_loaded": model_loaded,
    }