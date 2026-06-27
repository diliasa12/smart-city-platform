import pika
import json
import requests
import numpy as np
import time
import os
from app.core.model_loader import load_comfort_model, load_busy_hour_model

# Load model — unpack dict
saved = load_comfort_model()
comfort_model = saved['model']
label_encoder = saved['encoder']
busy_hour_model = load_busy_hour_model()

RABBITMQ_HOST = os.getenv("RABBITMQ_HOST", "localhost")
QUEUE_NAME = "telemetry_ml_queue"

def create_connection(retries=10, delay=5):
    for i in range(retries):
        try:
            print(f"Mencoba konek ke RabbitMQ ({i+1}/{retries})...")
            connection = pika.BlockingConnection(
                pika.ConnectionParameters(host=RABBITMQ_HOST)
            )
            print("Berhasil konek ke RabbitMQ!")
            return connection
        except pika.exceptions.AMQPConnectionError:
            print(f"Gagal, retry dalam {delay} detik...")
            time.sleep(delay)
    raise Exception("Tidak bisa konek ke RabbitMQ setelah beberapa kali retry.")

def process_message(ch, method, properties, body):
    try:
        payload = json.loads(body)
        print(f"\n Menerima data dari log_id: {payload.get('log_id')}")

        temperature = float(payload.get("temperature", 0.0))
        humidity = float(payload.get("humidity", 0.0))
        decibel_level = float(payload.get("decibel_level", 0.0))
        callback_url = payload.get("callback_url")

        comfort_features = np.array([[
            temperature, humidity, decibel_level, 0, 0
        ]])
        comfort_pred = int(comfort_model.predict(comfort_features)[0])
        status_kenyamanan = label_encoder.inverse_transform([comfort_pred])[0]

        busy_features = np.array([[temperature, decibel_level]])
        busy_pred = int(busy_hour_model.predict(busy_features)[0])

        print(f" Hasil: {status_kenyamanan}, jam sibuk: {busy_pred}")

        if callback_url:
            result_payload = {
                "telemetry_log_id": payload["log_id"],
                "ml_classification_status": status_kenyamanan,
                "predicted_next_busy_hour": busy_pred
            }
            try:
                response = requests.post(callback_url, json=result_payload, timeout=30)  # naik dari 5 → 30
                print(f"Callback ke Laravel sukses | Status: {response.status_code}")
            except requests.exceptions.Timeout:
                # Timeout bukan berarti gagal — PHP mungkin sudah proses tapi lambat reply
                print(f"Callback timeout tapi kemungkinan sudah diproses PHP, lanjut...")
            except requests.exceptions.ConnectionError as e:
                print(f"Callback gagal koneksi: {e}")
        else:
            print("Peringatan: callback_url tidak ditemukan di dalam payload!")

        # Tetap ack meski callback timeout — data sudah diproses ML
        ch.basic_ack(delivery_tag=method.delivery_tag)

    except Exception as e:
        print(f" Error saat memproses pesan: {str(e)}")
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)
        
def start_consumer():
    connection = create_connection()
    channel = connection.channel()
    channel.queue_declare(queue=QUEUE_NAME, durable=True)
    channel.basic_qos(prefetch_count=1)
    channel.basic_consume(queue=QUEUE_NAME, on_message_callback=process_message)
    print(f"Worker siap. Menunggu antrean di '{QUEUE_NAME}'. Tekan CTRL+C untuk keluar.")
    channel.start_consuming()

if __name__ == "__main__":
    start_consumer()