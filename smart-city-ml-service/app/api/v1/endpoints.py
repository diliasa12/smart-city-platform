import asyncio
import json
import os
import aio_pika
import httpx
import joblib
import pandas as pd
import numpy as np  
from fastapi import APIRouter

router = APIRouter()

# URL Callback
PHP_CALLBACK_URL = os.getenv("PHP_CALLBACK_URL", "http://php-service:8000/api/telemetry/callback")

model_rf = None
label_encoder = None
busy_hour_model = None

def load_models():
    global model_rf, label_encoder, busy_hour_model
    
    if model_rf is None or label_encoder is None:  
        try:
            from app.core.model_loader import load_comfort_model
            saved_data = load_comfort_model()
            model_rf = saved_data['model']
            label_encoder = saved_data['encoder']
            print(f"[Consumer] ✓ Berhasil memuat model comfort_classifier.pkl ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat model/encoder kamu: {e}")
            raise

    # Memuat Model Jam Sibuk
    if busy_hour_model is None:
        try:  
            from app.core.model_loader import load_busy_hour_model
            busy_hour_model = load_busy_hour_model()
            print(f"[Consumer] ✓ Berhasil memuat busy_hour_model milik teman ke memori.")
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memuat busy_hour_model milik teman: {e}")

def run_ml_pipeline(payload: dict) -> dict:
    load_models()
    
    suhu = float(payload.get("suhu", payload.get("temperature", 0)))
    kelembaban = float(payload.get("kelembaban", payload.get("humidity", 0)))
    kebisingan = float(payload.get("kebisingan", payload.get("decibel_level", 0)))
    hour = int(payload.get("hour", 0))
    is_weekend = int(payload.get("is_weekend", 0))
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

    # PREDIKSI JAM SIBUK
    busy_pred = 0
    if busy_hour_model is not None:
        try:
            busy_features = np.array([[
                suhu, 
                300.0,          
                kebisingan, 
                400.0,            
                1                
            ]])
            busy_pred = int(busy_hour_model.predict(busy_features)[0])
        except Exception as e:
            print(f"[Consumer] ✗ Gagal memprediksi jam sibuk: {e}")
    
    return {
        "status": "success",
        "comfort_status": comfort_status,
        "predicted_next_busy_hour": busy_pred,
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
        # 1. Proses JSON
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
            
        # 3. HTTP POST Callback PHP
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
    global model_rf, label_encoder
    if old_model_placeholder is not None:
        model_rf = old_model_placeholder.get('model')
        label_encoder = old_model_placeholder.get('encoder')
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