<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS env_sensor_readings (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                zone_id     INT UNSIGNED    NOT NULL COMMENT 'Lokasi Makro Outdoor',
                pm25        DECIMAL(8, 3)       NULL,
                pm10        DECIMAL(8, 3)       NULL,
                no2         DECIMAL(8, 3)       NULL,
                co          DECIMAL(8, 3)       NULL,
                o3          DECIMAL(8, 3)       NULL,
                temperature DECIMAL(5, 2)       NULL COMMENT 'Suhu Makro Luar Ruangan',
                humidity    DECIMAL(5, 2)       NULL COMMENT 'Kelembapan Makro Luar Ruangan',
                sensor_id   VARCHAR(100)        NULL,
                recorded_at DATETIME        NOT NULL,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                INDEX idx_env_readings_zone_id     (zone_id),
                INDEX idx_env_readings_recorded_at (recorded_at),
                CONSTRAINT fk_env_readings_zone FOREIGN KEY (zone_id)
                    REFERENCES shared_zones (id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS env_sensor_readings');
    }
};