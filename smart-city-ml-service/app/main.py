from fastapi import FastAPI
from app.api.v1 import endpoints

app = FastAPI(
    title="Smart City ML Service",
    description="Layanan AI untuk klasifikasi kenyamanan ruangan dan prediksi jam sibuk",
    version="1.0.0"
)

# Daftarkan router dari endpoints.py
app.include_router(endpoints.router, prefix="/api/v1")

@app.get("/")
def read_root():
    return {"message": "Smart City ML Service is running!"}