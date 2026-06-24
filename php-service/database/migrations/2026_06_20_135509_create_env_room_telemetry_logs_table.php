<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS env_room_telemetry_logs (
                id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                room_id                   INT UNSIGNED    NOT NULL,
                temperature               DECIMAL(5, 2)   NOT NULL COMMENT 'Data Mikro Indoor',
                humidity                  DECIMAL(5, 2)   NOT NULL COMMENT 'Data Mikro Indoor',
                decibel_level             DECIMAL(5, 2)   NOT NULL COMMENT 'Data Kebisingan Mikro',
                ml_classification_status  ENUM('nyaman', 'cukup_nyaman', 'tidak_nyaman') NOT NULL,
                predicted_next_busy_hour  TINYINT UNSIGNED NOT NULL,
                created_at                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                INDEX idx_room_telemetry_room_id (room_id),
                INDEX idx_room_telemetry_created_at (created_at),
                INDEX idx_room_telemetry_latest (room_id, created_at),
                CONSTRAINT fk_room_telemetry_room FOREIGN KEY (room_id)
                    REFERENCES env_rooms (id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS env_room_telemetry_logs');
    }
};