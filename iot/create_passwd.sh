#!/bin/sh
# ============================================================
#  iot/create_passwd.sh
#  Generate file passwd untuk Mosquitto menggunakan
#  mosquitto_passwd (sudah include di image eclipse-mosquitto)
#
#  Jalankan sekali sebelum docker compose up:
#    chmod +x iot/create_passwd.sh
#    docker run --rm -v $(pwd)/iot:/mosquitto/config \
#      eclipse-mosquitto:2.0 sh /mosquitto/config/create_passwd.sh
# ============================================================

PASSWD_FILE="/mosquitto/config/passwd"

echo "Membuat file passwd Mosquitto..."

# Hapus file lama jika ada
rm -f "$PASSWD_FILE"

# Format: mosquitto_passwd -b <file> <username> <password>

# IoT simulator / sensor devices
mosquitto_passwd -b "$PASSWD_FILE" iot_device    iot_secret

# Node-RED subscriber
mosquitto_passwd -b "$PASSWD_FILE" nodered       nodered_secret

# Admin (untuk monitoring/debugging)
mosquitto_passwd -b "$PASSWD_FILE" mqtt_admin    admin_secret_change_me

echo "File passwd berhasil dibuat: $PASSWD_FILE"
echo "Users:"
echo "  - iot_device    (sensor simulator)"
echo "  - nodered       (Node-RED bridge)"
echo "  - mqtt_admin    (admin/monitoring)"