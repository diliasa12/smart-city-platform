import os
import asyncio
from contextlib import asynccontextmanager
from dotenv import load_dotenv

# Load Environment Variables dari root folder (.env)
DOTENV_PATH = os.path.join(os.path.dirname(__file__), "..", "..", ".env")
load_dotenv(dotenv_path=DOTENV_PATH)

import aio_pika
from fastapi import FastAPI

# Import consumer milikmu & router HTTP milik temanmu
from app.api.v1.endpoints import start_consumer
from app.api.v1.seat_recommendation import router as seat_router
from app.core.model_loader import load_comfort_model  # Pastikan di model_loader juga memuat busy_hour_model jika diperlukan internal

RABBITMQ_URL = os.getenv("RABBITMQ_URL", "amqp://guest:guest@localhost:5672/")

@asynccontextmanager
async def lifespan(app: FastAPI):
    # 1. Load Model ML
    print("[ML Service] Memuat model ML ke memori...")
    app.state.comfort_model = load_comfort_model()
    print("[ML Service] ✓ Model berhasil dimuat.")

    # 2. Koneksi RabbitMQ
    print(f"[ML Service] Menghubungkan ke RabbitMQ: {RABBITMQ_URL} ...")
    rabbitmq_connection = await aio_pika.connect_robust(RABBITMQ_URL)
    app.state.rabbitmq_connection = rabbitmq_connection
    print("[ML Service] ✓ Koneksi RabbitMQ berhasil.")

    # 3. Consumer sebagai asyncio Background Task
    # Mengirimkan app.state.comfort_model (old_model_placeholder aman diabaikan di endpoints.py)
    consumer_task = asyncio.create_task(
        start_consumer(rabbitmq_connection, app.state.comfort_model),
        name="rabbitmq-consumer",
    )
    app.state.consumer_task = consumer_task
    print("[ML Service] ✓ Consumer task aktif — standby di 'telemetry_ml_queue'.")
    print("[ML Service] ✓ Server siap.\n")

    yield  # Server start & running

    # 4. Cleanup saat server dimatikan
    print("\n[ML Service] Menghentikan consumer task...")
    consumer_task.cancel()
    try:
        await consumer_task
    except asyncio.CancelledError:
        pass  
    print("[ML Service] ✓ Consumer task dihentikan.")

    print("[ML Service] Menutup koneksi RabbitMQ...")
    await rabbitmq_connection.close()
    print("[ML Service] ✓ Koneksi RabbitMQ ditutup.")

    del app.state.comfort_model
    del app.state.rabbitmq_connection
    del app.state.consumer_task
    print("[ML Service] ✓ Semua resource dibersihkan. Server berhenti.")


# Inisialisasi FastAPI terpadu
app = FastAPI(
    title="SmartCity ML & Analytics Service",
    description="Layanan AI terpadu untuk klasifikasi kenyamanan berbasis event-driven (RabbitMQ) dan rekomendasi infrastruktur.",
    version="2.1.0",
    lifespan=lifespan,
)

# --- MENDAFTARKAN FITUR HTTP TEMANMU ---
# Menyertakan router rekomendasi tempat duduk milik temanmu
app.include_router(seat_router, prefix="/api/v1", tags=["Seat Recommendation"])


@app.get("/", tags=["Root"])
def read_root():
    return {"message": "Smart City ML Service is running with RabbitMQ Background Worker active!"}


@app.get("/health", tags=["Health Check"])
async def health_check():
    model_loaded = hasattr(app.state, "comfort_model")
    mq_connected = hasattr(app.state, "rabbitmq_connection") and not app.state.rabbitmq_connection.is_closed
    consumer_running = hasattr(app.state, "consumer_task") and not app.state.consumer_task.done()

    overall = "ok" if (model_loaded and mq_connected and consumer_running) else "degraded"

    return {
        "status": overall,
        "service": "smartcity-ml-service",
        "version": "2.1.0",
        "model_loaded": model_loaded,
        "rabbitmq_connected": mq_connected,
        "consumer_running": consumer_running,
    }