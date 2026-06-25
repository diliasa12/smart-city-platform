from fastapi import FastAPI
from app.api.v1.endpoints import router as telemetry_router
from app.api.v1.seat_recommendation import router as seat_router

app = FastAPI(
    title="Smart City ML Service",
    description="Layanan AI untuk klasifikasi kenyamanan ruangan dan prediksi jam sibuk",
    version="1.1.0"
)

app.include_router(telemetry_router, prefix="/api/v1", tags=["Telemetry"])
app.include_router(seat_router, prefix="/api/v1", tags=["Seat Recommendation"])

@app.get("/")
def read_root():
    return {"message": "Smart City ML Service is running!"}

@app.get("/health")
def health():
    return {"status": "ok", "service": "smartcity-ml"}