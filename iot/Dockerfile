# ============================================================
#  iot/Dockerfile
#  Smart City — IoT Sensor Simulator
# ============================================================

FROM python:3.11-slim

WORKDIR /app

# Install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source
COPY simulator.py .

# Environment variables (bisa di-override via docker-compose)
ENV MQTT_BROKER=mosquitto
ENV MQTT_PORT=1883
ENV MQTT_USERNAME=iot_device
ENV MQTT_PASSWORD=iot_secret
ENV MQTT_INTERVAL=30
ENV MQTT_CLIENT_ID=smartcity-simulator

# Healthcheck — pastikan simulator masih berjalan
HEALTHCHECK --interval=60s --timeout=10s --start-period=10s --retries=3 \
  CMD pgrep -f simulator.py || exit 1

CMD ["python", "simulator.py"]