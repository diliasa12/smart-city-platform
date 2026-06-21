from contextlib import asynccontextmanager
from fastapi import FastAPI
from app.api.v1 import endpoints
from app.core.model_loader import load_comfort_model


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Lifespan context manager FastAPI.
    Model hanya dimuat SEKALI saat server startup, lalu di-share
    ke seluruh request tanpa reload — menghemat memori & latency.
    Disimpan di app.state agar bisa diakses dari endpoint via request.app.state.
    """
    print("[ML Service] Memuat model comfort_classifier.pkl ke memori...")
    app.state.comfort_model = load_comfort_model()
    print("[ML Service] Model berhasil dimuat. Server siap menerima request.")

    yield  # Server berjalan di sini

    # Cleanup saat server shutdown
    del app.state.comfort_model
    print("[ML Service] Model dibersihkan dari memori. Server berhenti.")


# ──────────────────────────────────────────────
# Inisialisasi Aplikasi FastAPI
# ──────────────────────────────────────────────
app = FastAPI(
    title="SmartCity ML & Analytics Service",
    description=(
        "Layanan komputasi internal berbasis mikro untuk memproses "
        "klasifikasi kenyamanan akustik ruangan secara real-time "
        "menggunakan model Random Forest yang telah dilatih."
    ),
    version="1.0.0",
    lifespan=lifespan,
)


# ──────────────────────────────────────────────
# Daftarkan Router
# ──────────────────────────────────────────────
app.include_router(
    endpoints.router,
    prefix="/api/v1",
    tags=["Analitik & Klasifikasi"],
)


@app.get("/health", tags=["Health Check"])
async def health_check():
    """Endpoint pengecekan kesehatan service."""
    model_loaded = hasattr(app.state, "comfort_model")
    return {
        "status": "ok" if model_loaded else "degraded",
        "service": "smartcity-ml-service",
        "model_loaded": model_loaded,
    }
