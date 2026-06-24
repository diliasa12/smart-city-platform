import pika
import json
import requests
import numpy as np
import os
from core.model_loader import load_comfort_model, load_busy_hour_model

# Load model 
comfort_model = load_comfort_model()
busy_hour_model = load_busy_hour_model()

# Konfigurasi RabbitMQ 
RABBITMQ_HOST = os.getenv("RABBITMQ_HOST", "localhost")
QUEUE_NAME = "telemetry_ml_queue"

def process_message(ch, method, properties, body):
    try:
        # Parse payload dari Laravel
        payload = json.loads(body)
        print(f"\n[x] Menerima data dari log_id: {payload.get('log_id')}")

        temperature = float(payload.get("temperature", 0.0))
        humidity = float(payload.get("humidity", 0.0))
        decibel_level = float(payload.get("decibel_level", 0.0))
        callback_url = payload.get("callback_url")

        # fitur untuk Model Kenyamanan 
        comfort_features = np.array([[
            temperature, humidity, decibel_level, 
            0,       # near_construction 
            500.0,   # population_density 
            0,       # public_event 
            1        # school_zone 
        ]])
        
        comfort_pred = int(comfort_model.predict(comfort_features)[0])
        status_kenyamanan = "nyaman" if comfort_pred == 0 else "tidak_nyaman" if comfort_pred == 1 else "cukup_nyaman"

        # fitur untuk Model Jam Sibuk
        busy_features = np.array([[
            temperature, 
            300.0,           # light 
            decibel_level, 
            400.0,           # co2 
            1                # pir 
        ]])
        
        busy_pred = int(busy_hour_model.predict(busy_features)[0])

        # Kirim hasil prediksi kembali ke Laravel
        if callback_url:
            result_payload = {
                "telemetry_log_id": payload["log_id"],
                "ml_classification_status": status_kenyamanan,
                "predicted_next_busy_hour": busy_pred
            }
            
            # Timeout 5 detik
            response = requests.post(callback_url, json=result_payload, timeout=5)
            print(f"[v] Callback ke Laravel sukses | Status: {response.status_code}")
        else:
            print("[!] Peringatan: callback_url tidak ditemukan di dalam payload!")

        # Beri tahu RabbitMQ bahwa pesan berhasil diproses 
        ch.basic_ack(delivery_tag=method.delivery_tag)

    except Exception as e:
        print(f"[X] Error saat memproses pesan: {str(e)}")
        # Tolak pesan jika gagal 
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)

def start_consumer():
    print(f"[*] Menghubungkan ke RabbitMQ di {RABBITMQ_HOST}...")
    connection = pika.BlockingConnection(pika.ConnectionParameters(host=RABBITMQ_HOST))
    channel = connection.channel()
    
    # Deklarasi queue 
    channel.queue_declare(queue=QUEUE_NAME, durable=True)
    
    # Jangan beri pesan baru sebelum pesan sebelumnya selesai
    channel.basic_qos(prefetch_count=1)
    channel.basic_consume(queue=QUEUE_NAME, on_message_callback=process_message)
    
    print(f"[*] Worker siap. Menunggu antrean di '{QUEUE_NAME}'. Tekan CTRL+C untuk keluar.")
    channel.start_consuming()

if __name__ == "__main__":
    start_consumer()