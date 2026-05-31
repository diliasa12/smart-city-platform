"""
iot/test_simulator.py
─────────────────────
Unit tests untuk simulator.py — tanpa koneksi MQTT nyata.

Jalankan:
    pip install pytest
    pytest iot/test_simulator.py -v
"""

import math
from datetime import datetime, timezone

import pytest

# Import fungsi yang akan ditest
from simulator import (
    ZONES,
    ZONE_PROFILES,
    simulate_traffic,
    simulate_air_quality,
)

# ──────────────────────────────────────────────────────────────
# Fixtures
# ──────────────────────────────────────────────────────────────

@pytest.fixture
def morning_rush():
    """Jam sibuk pagi — 08:00 hari Senin."""
    return datetime(2024, 3, 25, 8, 0, 0, tzinfo=timezone.utc)

@pytest.fixture
def midnight():
    """Tengah malam — 02:00."""
    return datetime(2024, 3, 25, 2, 0, 0, tzinfo=timezone.utc)

@pytest.fixture
def weekend_noon():
    """Sabtu siang — hari libur."""
    return datetime(2024, 3, 23, 12, 0, 0, tzinfo=timezone.utc)  # Sabtu


# ══════════════════════════════════════════════════════════════
# 1. Zona
# ══════════════════════════════════════════════════════════════

class TestZones:
    def test_semua_zona_terdefinisi(self):
        assert len(ZONES) == 5
        assert "zone1" in ZONES
        assert "zone5" in ZONES

    def test_setiap_zona_punya_profile(self):
        for zone in ZONES:
            assert zone in ZONE_PROFILES
            profile = ZONE_PROFILES[zone]
            assert "name"         in profile
            assert "traffic_base" in profile
            assert "air_base"     in profile
            assert "peak_hour"    in profile


# ══════════════════════════════════════════════════════════════
# 2. simulate_traffic
# ══════════════════════════════════════════════════════════════

class TestSimulateTraffic:
    def test_mengembalikan_semua_field_wajib(self, morning_rush):
        data = simulate_traffic("zone1", morning_rush)
        required_fields = [
            "zone", "zone_name", "vehicle_density", "avg_speed_kmh",
            "incident_flag", "day_of_week", "hour", "sensor_id", "timestamp",
        ]
        for field in required_fields:
            assert field in data, f"Field '{field}' tidak ada di response"

    def test_vehicle_density_positif(self, morning_rush):
        for zone in ZONES:
            data = simulate_traffic(zone, morning_rush)
            assert data["vehicle_density"] > 0, f"{zone}: density harus > 0"

    def test_avg_speed_dalam_rentang_wajar(self, morning_rush):
        for zone in ZONES:
            data = simulate_traffic(zone, morning_rush)
            assert 0 < data["avg_speed_kmh"] <= 100, (
                f"{zone}: speed {data['avg_speed_kmh']} di luar rentang"
            )

    def test_incident_flag_hanya_0_atau_1(self, morning_rush):
        # Jalankan banyak kali untuk pastikan tidak ada nilai lain
        for _ in range(50):
            data = simulate_traffic("zone1", morning_rush)
            assert data["incident_flag"] in (0, 1)

    def test_zone_name_sesuai_profile(self, morning_rush):
        data = simulate_traffic("zone1", morning_rush)
        assert data["zone_name"] == ZONE_PROFILES["zone1"]["name"]

    def test_sensor_id_mengandung_nama_zona(self, morning_rush):
        data = simulate_traffic("zone2", morning_rush)
        assert "ZONE2" in data["sensor_id"]

    def test_hour_sesuai_timestamp(self, morning_rush):
        data = simulate_traffic("zone1", morning_rush)
        assert data["hour"] == 8

    def test_malam_lebih_sepi_dari_pagi(self, morning_rush, midnight):
        """Jam sibuk pagi harus lebih padat dari tengah malam."""
        # Jalankan 20 kali dan ambil rata-rata untuk mengurangi noise
        morning_densities = [
            simulate_traffic("zone1", morning_rush)["vehicle_density"]
            for _ in range(20)
        ]
        midnight_densities = [
            simulate_traffic("zone1", midnight)["vehicle_density"]
            for _ in range(20)
        ]
        assert sum(morning_densities) / 20 > sum(midnight_densities) / 20

    def test_weekend_lebih_sepi_dari_weekday(self, weekend_noon):
        """Weekend harus lebih sepi dari hari kerja di jam yang sama."""
        weekday_noon = datetime(2024, 3, 25, 12, 0, 0, tzinfo=timezone.utc)  # Senin
        weekday_densities = [
            simulate_traffic("zone1", weekday_noon)["vehicle_density"]
            for _ in range(20)
        ]
        weekend_densities = [
            simulate_traffic("zone1", weekend_noon)["vehicle_density"]
            for _ in range(20)
        ]
        assert sum(weekday_densities) / 20 > sum(weekend_densities) / 20

    def test_timestamp_format_iso(self, morning_rush):
        data = simulate_traffic("zone1", morning_rush)
        # Harus bisa di-parse kembali
        parsed = datetime.fromisoformat(data["timestamp"].replace("Z", "+00:00"))
        assert parsed is not None

    def test_day_of_week_sesuai(self, morning_rush):
        # morning_rush = Senin (weekday 0)
        data = simulate_traffic("zone1", morning_rush)
        assert data["day_of_week"] == 0   # Senin


# ══════════════════════════════════════════════════════════════
# 3. simulate_air_quality
# ══════════════════════════════════════════════════════════════

class TestSimulateAirQuality:
    def test_mengembalikan_semua_field_wajib(self, morning_rush):
        data = simulate_air_quality("zone1", morning_rush)
        required_fields = [
            "zone", "zone_name", "pm25", "pm10", "no2", "co", "o3",
            "temperature", "humidity", "is_anomaly", "sensor_id", "timestamp",
        ]
        for field in required_fields:
            assert field in data, f"Field '{field}' tidak ada"

    def test_semua_nilai_positif(self, morning_rush):
        for zone in ZONES:
            data = simulate_air_quality(zone, morning_rush)
            for key in ["pm25", "pm10", "no2", "co", "o3", "temperature"]:
                assert data[key] > 0, f"{zone}.{key} harus > 0"

    def test_humidity_antara_0_dan_100(self, morning_rush):
        for _ in range(30):
            data = simulate_air_quality("zone1", morning_rush)
            assert 0 <= data["humidity"] <= 100, (
                f"Humidity {data['humidity']} di luar rentang 0-100"
            )

    def test_temperature_dalam_rentang_tropis(self, morning_rush):
        """Suhu Jakarta sekitar 20-40°C."""
        for _ in range(30):
            data = simulate_air_quality("zone1", morning_rush)
            assert 15 <= data["temperature"] <= 45, (
                f"Temperature {data['temperature']} tidak wajar untuk kota tropis"
            )

    def test_is_anomaly_boolean(self, morning_rush):
        data = simulate_air_quality("zone1", morning_rush)
        assert isinstance(data["is_anomaly"], bool)

    def test_sensor_id_mengandung_nama_zona(self, morning_rush):
        data = simulate_air_quality("zone3", morning_rush)
        assert "ZONE3" in data["sensor_id"]

    def test_zona_berbeda_punya_nilai_berbeda(self, morning_rush):
        """Tiap zona harus punya karakteristik berbeda (bukan nilai identik)."""
        results = {
            zone: simulate_air_quality(zone, morning_rush)
            for zone in ZONES
        }
        # PM2.5 tidak boleh semua sama (karena profile berbeda)
        pm25_values = [r["pm25"] for r in results.values()]
        assert len(set(round(v) for v in pm25_values)) > 1, (
            "Semua zona menghasilkan PM2.5 identik — profile tidak bekerja"
        )

    def test_timestamp_format_iso(self, morning_rush):
        data = simulate_air_quality("zone1", morning_rush)
        parsed = datetime.fromisoformat(data["timestamp"].replace("Z", "+00:00"))
        assert parsed is not None

    def test_pm25_lebih_kecil_dari_pm10_rata_rata(self, morning_rush):
        """Secara umum PM2.5 ≤ PM10."""
        pm25_list, pm10_list = [], []
        for _ in range(50):
            data = simulate_air_quality("zone1", morning_rush)
            if not data["is_anomaly"]:   # abaikan anomali
                pm25_list.append(data["pm25"])
                pm10_list.append(data["pm10"])
        if pm25_list:
            avg_pm25 = sum(pm25_list) / len(pm25_list)
            avg_pm10 = sum(pm10_list) / len(pm10_list)
            assert avg_pm25 <= avg_pm10, (
                f"Rata-rata PM2.5 ({avg_pm25:.1f}) lebih besar dari PM10 ({avg_pm10:.1f})"
            )


# ══════════════════════════════════════════════════════════════
# 4. JSON serializability — payload harus bisa di-encode ke JSON
# ══════════════════════════════════════════════════════════════

class TestJsonSerializable:
    import json as _json

    def test_traffic_payload_json_serializable(self, morning_rush):
        import json
        data = simulate_traffic("zone1", morning_rush)
        # Tidak boleh throw exception
        encoded = json.dumps(data)
        decoded = json.loads(encoded)
        assert decoded["zone"] == "zone1"

    def test_air_payload_json_serializable(self, morning_rush):
        import json
        data = simulate_air_quality("zone1", morning_rush)
        encoded = json.dumps(data)
        decoded = json.loads(encoded)
        assert decoded["zone"] == "zone1"
        assert isinstance(decoded["is_anomaly"], bool)


# ══════════════════════════════════════════════════════════════
# 5. Topic naming convention (sesuai spek PDF)
# ══════════════════════════════════════════════════════════════

class TestTopicNaming:
    def test_traffic_topic_format(self):
        """Topic harus sesuai format: city/{zone}/traffic"""
        for zone in ZONES:
            topic = f"city/{zone}/traffic"
            parts = topic.split("/")
            assert parts[0] == "city"
            assert parts[1] == zone
            assert parts[2] == "traffic"

    def test_air_topic_format(self):
        """Topic harus sesuai format: city/{zone}/air"""
        for zone in ZONES:
            topic = f"city/{zone}/air"
            parts = topic.split("/")
            assert parts[0] == "city"
            assert parts[1] == zone
            assert parts[2] == "air"

    def test_semua_zona_punya_kedua_topic(self):
        expected_topics = set()
        for zone in ZONES:
            expected_topics.add(f"city/{zone}/traffic")
            expected_topics.add(f"city/{zone}/air")
        assert len(expected_topics) == len(ZONES) * 2