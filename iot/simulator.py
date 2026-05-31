"""
iot/simulator.py
────────────────────────────────────────────────────────────────
Smart City — IoT Sensor Simulator  (Per. 14 / Tambahan IoT)

Mensimulasikan sensor lalu lintas dan kualitas udara di 4 zona
kota, lalu mem-publish data secara periodik ke Mosquitto MQTT
Broker menggunakan protokol MQTT (paho-mqtt).

Topic naming convention (sesuai spek PDF):
  city/{zone}/traffic   →  data lalu lintas
  city/{zone}/air       →  data kualitas udara

Contoh topic:
  city/zone1/traffic
  city/zone1/air
  city/zone2/traffic
  ...

Jalankan:
  python simulator.py
  python simulator.py --broker localhost --port 1883 --interval 30

Variabel env (override lewat .env atau export):
  MQTT_BROKER   = localhost
  MQTT_PORT     = 1883
  MQTT_USERNAME = iot_device
  MQTT_PASSWORD = iot_secret
  MQTT_INTERVAL = 30       (detik antar publish)
  MQTT_CLIENT_ID = smartcity-simulator
"""

import argparse
import json
import logging
import math
import os
import random
import signal
import sys
import time
from datetime import datetime, timezone

import paho.mqtt.client as mqtt

# ──────────────────────────────────────────────────────────────
# Logging
# ──────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
log = logging.getLogger("iot-simulator")

# ──────────────────────────────────────────────────────────────
# Config
# ──────────────────────────────────────────────────────────────
ZONES = ["zone1", "zone2", "zone3", "zone4", "zone5"]

# Karakteristik unik tiap zona agar data tidak identik
ZONE_PROFILES = {
    "zone1": {"name": "Pusat",   "traffic_base": 35, "air_base": 1.2, "peak_hour": 8},
    "zone2": {"name": "Utara",   "traffic_base": 25, "air_base": 0.9, "peak_hour": 7},
    "zone3": {"name": "Selatan", "traffic_base": 30, "air_base": 1.1, "peak_hour": 8},
    "zone4": {"name": "Timur",   "traffic_base": 20, "air_base": 0.8, "peak_hour": 9},
    "zone5": {"name": "Barat",   "traffic_base": 22, "air_base": 0.7, "peak_hour": 8},
}

# ──────────────────────────────────────────────────────────────
# Simulasi Sensor
# ──────────────────────────────────────────────────────────────

def simulate_traffic(zone: str, now: datetime) -> dict:
    """
    Simulasikan data sensor lalu lintas.
    Pola: padat saat jam sibuk pagi (07-09) dan sore (16-18),
    rendah malam hari, pengaruh hari kerja vs weekend.
    """
    profile   = ZONE_PROFILES[zone]
    hour      = now.hour
    weekday   = now.weekday()   # 0=Senin, 6=Minggu
    peak      = profile["peak_hour"]
    base      = profile["traffic_base"]

    # Pola kepadatan harian menggunakan sin — jam sibuk pagi & sore
    morning_peak = base * (1 + 1.6 * math.exp(-0.5 * ((hour - peak) / 1.5) ** 2))
    evening_peak = base * (1 + 1.2 * math.exp(-0.5 * ((hour - 17)  / 1.5) ** 2))
    density_base = max(morning_peak, evening_peak)

    # Weekend lebih sepi ~30%
    if weekday >= 5:
        density_base *= 0.70

    # Malam hari sangat sepi
    if hour < 5 or hour >= 22:
        density_base *= 0.20

    # Tambah noise Gaussian
    vehicle_density = max(2, density_base + random.gauss(0, base * 0.15))

    # Kecepatan berbanding terbalik dengan kepadatan
    max_speed   = 80.0
    min_speed   = 5.0
    norm_density = min(vehicle_density / (base * 2.5), 1.0)
    avg_speed   = max(min_speed, max_speed * (1 - norm_density) + random.gauss(0, 3))

    # Insiden acak: probabilitas lebih tinggi saat padat
    incident_prob  = 0.03 + (norm_density * 0.10)
    incident_flag  = 1 if random.random() < incident_prob else 0

    return {
        "zone":             zone,
        "zone_name":        profile["name"],
        "vehicle_density":  round(vehicle_density, 1),   # kendaraan/menit
        "avg_speed_kmh":    round(avg_speed, 1),
        "incident_flag":    incident_flag,
        "day_of_week":      weekday,
        "hour":             hour,
        "sensor_id":        f"TRAFFIC-{zone.upper()}-01",
        "timestamp":        now.isoformat(),
    }


def simulate_air_quality(zone: str, now: datetime) -> dict:
    """
    Simulasikan pembacaan sensor kualitas udara.
    Kualitas udara lebih buruk saat jam sibuk (traffic tinggi) dan
    siang hari (suhu tinggi). Tiap zona punya baseline berbeda.
    """
    profile = ZONE_PROFILES[zone]
    hour    = now.hour
    mult    = profile["air_base"]

    # PM2.5 lebih tinggi saat jam sibuk
    traffic_factor = 1.0
    if 6 <= hour <= 10 or 15 <= hour <= 20:
        traffic_factor = 1.5 + random.uniform(0, 0.5)

    pm25 = max(5.0,  (20 + random.uniform(-5, 15))  * mult * traffic_factor)
    pm10 = max(8.0,  (35 + random.uniform(-8, 20))  * mult * traffic_factor)
    no2  = max(5.0,  (40 + random.uniform(-10, 30)) * mult * traffic_factor)
    co   = max(0.3,  (1.0 + random.uniform(-0.3, 1.5)) * mult)
    o3   = max(10.0, (60 + random.uniform(-20, 40)) / mult)   # O3 berbanding terbalik

    # Suhu & kelembaban: panas siang, lebih sejuk malam
    temp_base  = 28 + 6 * math.sin((hour - 6) * math.pi / 12)
    temperature = round(temp_base + random.gauss(0, 1.5), 1)
    humidity    = round(max(40, min(95, 72 - (temperature - 28) * 1.2 + random.gauss(0, 4))), 1)

    # Anomali sesekali (5% chance) — untuk test anomaly detector
    anomaly = random.random() < 0.05
    if anomaly:
        pm25 *= random.uniform(3, 6)
        log.warning(f"[{zone}] ANOMALY injected — PM2.5={pm25:.1f}")

    return {
        "zone":         zone,
        "zone_name":    profile["name"],
        "pm25":         round(pm25,  2),
        "pm10":         round(pm10,  2),
        "no2":          round(no2,   2),
        "co":           round(co,    3),
        "o3":           round(o3,    2),
        "temperature":  temperature,
        "humidity":     humidity,
        "is_anomaly":   anomaly,
        "sensor_id":    f"ENV-{zone.upper()}-01",
        "timestamp":    now.isoformat(),
    }


# ──────────────────────────────────────────────────────────────
# MQTT Client
# ──────────────────────────────────────────────────────────────

class SmartCitySimulator:
    def __init__(self, broker: str, port: int, username: str, password: str,
                 client_id: str, interval: int):
        self.broker    = broker
        self.port      = port
        self.username  = username
        self.password  = password
        self.client_id = client_id
        self.interval  = interval
        self.running   = False

        # Statistik publish
        self.stats = {
            "published":  0,
            "errors":     0,
            "last_cycle": None,
        }

        # Setup MQTT client
        self.client = mqtt.Client(
            client_id=client_id,
            clean_session=True,
            protocol=mqtt.MQTTv311,
        )
        self.client.username_pw_set(username, password)

        # Callbacks
        self.client.on_connect    = self._on_connect
        self.client.on_disconnect = self._on_disconnect
        self.client.on_publish    = self._on_publish

        # Last Will Testament — broker broadcast jika simulator mati tiba-tiba
        lwt_payload = json.dumps({
            "status":    "offline",
            "client_id": client_id,
            "timestamp": datetime.now(timezone.utc).isoformat(),
        })
        self.client.will_set(
            topic   = f"city/simulator/status",
            payload = lwt_payload,
            qos     = 1,
            retain  = True,
        )

    # ── Callbacks ─────────────────────────────────────────────

    def _on_connect(self, client, userdata, flags, rc):
        codes = {
            0: "Connected successfully",
            1: "Refused — wrong protocol version",
            2: "Refused — invalid client ID",
            3: "Refused — server unavailable",
            4: "Refused — bad username/password",
            5: "Refused — not authorised",
        }
        msg = codes.get(rc, f"Unknown code {rc}")
        if rc == 0:
            log.info(f"MQTT {msg} → {self.broker}:{self.port}")
            # Publish status online
            self.client.publish(
                topic   = "city/simulator/status",
                payload = json.dumps({
                    "status":    "online",
                    "client_id": self.client_id,
                    "zones":     ZONES,
                    "interval":  self.interval,
                    "timestamp": datetime.now(timezone.utc).isoformat(),
                }),
                qos    = 1,
                retain = True,
            )
        else:
            log.error(f"MQTT connection refused: {msg}")
            self.running = False

    def _on_disconnect(self, client, userdata, rc):
        if rc != 0:
            log.warning(f"MQTT unexpected disconnect (rc={rc}), akan reconnect...")
        else:
            log.info("MQTT disconnected cleanly.")

    def _on_publish(self, client, userdata, mid):
        pass   # mid = message ID, dikonfirmasi broker

    # ── Connect & Run ─────────────────────────────────────────

    def connect(self):
        log.info(f"Menghubungkan ke broker {self.broker}:{self.port} ...")
        try:
            self.client.connect(
                host      = self.broker,
                port      = self.port,
                keepalive = 60,
            )
            self.client.loop_start()   # background thread untuk network I/O
        except Exception as e:
            log.error(f"Gagal konek ke broker: {e}")
            sys.exit(1)

    def disconnect(self):
        log.info("Simulator berhenti, disconnect dari broker...")
        self.client.publish(
            topic   = "city/simulator/status",
            payload = json.dumps({
                "status":    "offline",
                "client_id": self.client_id,
                "timestamp": datetime.now(timezone.utc).isoformat(),
            }),
            qos    = 1,
            retain = True,
        )
        time.sleep(0.5)
        self.client.loop_stop()
        self.client.disconnect()

    def publish_sensor_data(self):
        """Satu siklus publish — semua zona, semua tipe sensor."""
        now     = datetime.now(timezone.utc)
        cycle   = now.strftime("%Y-%m-%dT%H:%M:%S")
        success = 0
        errors  = 0

        for zone in ZONES:
            # ── Traffic sensor ───────────────────────────────
            try:
                traffic_data    = simulate_traffic(zone, now)
                traffic_topic   = f"city/{zone}/traffic"
                traffic_payload = json.dumps(traffic_data, ensure_ascii=False)

                result = self.client.publish(
                    topic   = traffic_topic,
                    payload = traffic_payload,
                    qos     = 1,         # at least once delivery
                    retain  = False,
                )
                if result.rc == mqtt.MQTT_ERR_SUCCESS:
                    success += 1
                    log.info(
                        f"[{zone}/traffic] density={traffic_data['vehicle_density']:6.1f} "
                        f"speed={traffic_data['avg_speed_kmh']:5.1f} km/h "
                        f"incident={traffic_data['incident_flag']}"
                    )
                else:
                    errors += 1
                    log.error(f"Publish gagal [{traffic_topic}]: rc={result.rc}")

            except Exception as e:
                errors += 1
                log.error(f"Error publish traffic [{zone}]: {e}")

            # ── Air quality sensor ───────────────────────────
            try:
                air_data    = simulate_air_quality(zone, now)
                air_topic   = f"city/{zone}/air"
                air_payload = json.dumps(air_data, ensure_ascii=False)

                result = self.client.publish(
                    topic   = air_topic,
                    payload = air_payload,
                    qos     = 1,
                    retain  = False,
                )
                if result.rc == mqtt.MQTT_ERR_SUCCESS:
                    success += 1
                    log.info(
                        f"[{zone}/air    ] PM2.5={air_data['pm25']:6.2f} "
                        f"PM10={air_data['pm10']:6.2f} "
                        f"Temp={air_data['temperature']:5.1f}°C "
                        f"Hum={air_data['humidity']:5.1f}%"
                        + (" ⚠ ANOMALY" if air_data["is_anomaly"] else "")
                    )
                else:
                    errors += 1
                    log.error(f"Publish gagal [{air_topic}]: rc={result.rc}")

            except Exception as e:
                errors += 1
                log.error(f"Error publish air [{zone}]: {e}")

        # Update statistik
        self.stats["published"]  += success
        self.stats["errors"]     += errors
        self.stats["last_cycle"]  = cycle

        log.info(
            f"Cycle selesai [{cycle}] — "
            f"{success}/{success+errors} berhasil | "
            f"total published: {self.stats['published']}"
        )

    def run(self):
        """Loop utama — publish setiap self.interval detik."""
        self.running = True
        self.connect()

        # Tunggu sebentar agar koneksi established
        time.sleep(2)

        log.info(
            f"Simulator berjalan — {len(ZONES)} zona × 2 tipe sensor "
            f"= {len(ZONES)*2} pesan per siklus, interval {self.interval}s"
        )

        while self.running:
            try:
                self.publish_sensor_data()
            except KeyboardInterrupt:
                break
            except Exception as e:
                log.error(f"Error dalam publish cycle: {e}")
                self.stats["errors"] += 1

            # Tidur sampai interval berikutnya
            # Gunakan loop kecil agar bisa interrupt dengan Ctrl+C
            for _ in range(self.interval * 10):
                if not self.running:
                    break
                time.sleep(0.1)

        self.disconnect()
        log.info(
            f"Simulator selesai. "
            f"Total published: {self.stats['published']}, "
            f"errors: {self.stats['errors']}"
        )


# ──────────────────────────────────────────────────────────────
# CLI & Entry Point
# ──────────────────────────────────────────────────────────────

def parse_args():
    parser = argparse.ArgumentParser(
        description="Smart City IoT Sensor Simulator",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument("--broker",    default=os.getenv("MQTT_BROKER",    "localhost"))
    parser.add_argument("--port",      default=int(os.getenv("MQTT_PORT",  "1883")),   type=int)
    parser.add_argument("--username",  default=os.getenv("MQTT_USERNAME",  "iot_device"))
    parser.add_argument("--password",  default=os.getenv("MQTT_PASSWORD",  "iot_secret"))
    parser.add_argument("--client-id", default=os.getenv("MQTT_CLIENT_ID", "smartcity-simulator"))
    parser.add_argument("--interval",  default=int(os.getenv("MQTT_INTERVAL", "30")), type=int,
                        help="Interval publish dalam detik")
    parser.add_argument("--once",      action="store_true",
                        help="Publish satu siklus saja lalu exit (untuk testing)")
    return parser.parse_args()


def main():
    args = parse_args()

    simulator = SmartCitySimulator(
        broker    = args.broker,
        port      = args.port,
        username  = args.username,
        password  = args.password,
        client_id = args.client_id,
        interval  = args.interval,
    )

    # Graceful shutdown dengan Ctrl+C atau SIGTERM (Docker stop)
    def shutdown(signum, frame):
        log.info(f"Signal {signum} diterima, menghentikan simulator...")
        simulator.running = False

    signal.signal(signal.SIGINT,  shutdown)
    signal.signal(signal.SIGTERM, shutdown)

    if args.once:
        # Mode testing: connect, publish sekali, disconnect
        simulator.connect()
        time.sleep(2)
        simulator.publish_sensor_data()
        time.sleep(1)
        simulator.disconnect()
    else:
        simulator.run()


if __name__ == "__main__":
    main()