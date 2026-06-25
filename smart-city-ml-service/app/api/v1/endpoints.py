import asyncio
import json
import os
import aio_pika
import httpx
import joblib
import pandas as pd
from fastapi import APIRouter

router = APIRouter()

# URL Callback
PHP_CALLBACK_URL = os.getenv("PHP_CALLBACK_URL", "http://localhost:3000/api/telemetry/callback")

MODELS_DIR = os.path.join(os.path.dirname(__file__), "..", "..", "models")
MODEL_PATH = os.path.join(MODELS_DIR, "comfort_classifier.pkl")

model_rf = None
label_encoder = None

def load_models():

    global model_rf, label_encoder
    if model_rf is None or label_encoder is None:
        try:
            saved_data = joblib.load(MODEL_PATH)
        
            model_rf = saved_data['model']
            label_encoder = saved_data['encoder']
            print(f"[Consumer] ✓ Berhasil memuat model comfort_classifier.pkl ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat model/encoder: {e}")
            raise

def run_ml_pipeline(payload: dict) -> dict:

    load_models()
    
    suhu = payload.get("suhu", 0)
    kelembaban = payload.get("kelembaban", 0)
    kebisingan = payload.get("kebisingan", 0)
    hour = payload.get("hour", 0)
    is_weekend = payload.get("is_weekend", 0)
    log_id = payload.get("log_id")
    device_id = payload.get("device_id", "unknown")
    
    features = pd.DataFrame([{
        'suhu': suhu,
        'kelembaban': kelembaban,
        'kebisingan': kebisingan,
        'hour': hour,
        'is_weekend': is_weekend
    }])
    
    prediction_encoded = model_rf.predict(features)[0]
    
    comfort_status = label_encoder.inverse_transform([prediction_encoded])[0]
    
    return {
        "status": "success",
        "comfort_status": comfort_status,
        "metadata": {
            "log_id": log_id,
            "device_id": device_id,
            "suhu": suhu,
            "kelembaban": kelembaban,
            "kebisingan": kebisingan,
            "hour": hour,
            "is_weekend": is_weekend
        }
    }

async def process_message(message: aio_pika.IncomingMessage) -> None:
    
    async with message.process(requeue=True):
        # 1. proses JSON
        try:
            payload = json.loads(message.body.decode())
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memproses JSON message: {e} — message di-reject.")
            raise

        if not payload:
            print("[Consumer] ✗ Payload kosong — message diabaikan.")
            return

        log_id = payload.get("log_id")
        if log_id is None:
            print("[Consumer] ✗ Field 'log_id' tidak ditemukan — message diabaikan.")
            return

        # 2. Penggolongan
        try:
            loop = asyncio.get_event_loop()
            result_payload = await loop.run_in_executor(
                None,
                run_ml_pipeline,
                payload
            )
            print(f"[Consumer] ✓ Penggolongan selesai — Log ID {log_id}: {result_payload['comfort_status']}")
        except Exception as e:
            print(f"[Consumer] ✗ Error saat penggolongan untuk Log ID {log_id}: {e}")
            raise 
            
        # 3. HTTP POST Callback ke PHP
        try:
            async with httpx.AsyncClient(timeout=10.0) as client:
                response = await client.post(
                    PHP_CALLBACK_URL,
                    json=result_payload,
                    headers={"Content-Type": "application/json"},
                )
                print(f"[Consumer] ✓ Callback terkirim ke PHP → HTTP {response.status_code}")
        except httpx.ConnectError:
            print(f"[Consumer] ✗ Callback GAGAL: PHP server tidak dapat dijangkau di '{PHP_CALLBACK_URL}'. Worker tetap berjalan.")
        except httpx.TimeoutException:
            print(f"[Consumer] ✗ Callback TIMEOUT ke '{PHP_CALLBACK_URL}'. Worker tetap berjalan.")
        except Exception as e:
            print(f"[Consumer] ✗ Callback error tak terduga: {e}. Worker tetap berjalan.")

async def start_consumer(connection: aio_pika.RobustConnection, old_model_placeholder=None) -> None:

    try:
        load_models()
    except Exception as e:
        print("[Consumer] ⚠️ Peringatan Critical: File model 'comfort_classifier.pkl' tidak ditemukan atau gagal dimuat di memori!")
        
    channel = await connection.channel()
    
    await channel.set_qos(prefetch_count=1)
    
    queue = await channel.declare_queue("telemetry_ml_queue", durable=True)
    
    print("[Consumer] ✓ Standby menunggu queue 'telemetry_ml_queue'...")
    
    await queue.consume(
        lambda msg: asyncio.ensure_future(process_message(msg))
    )
    
    await asyncio.Future()