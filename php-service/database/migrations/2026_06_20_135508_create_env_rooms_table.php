<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS env_rooms (
                id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                zone_id      INT UNSIGNED    NOT NULL,
                room_name    VARCHAR(100)    NOT NULL,
                capacity     INT UNSIGNED    NOT NULL DEFAULT 0,
                device_token VARCHAR(128)    NOT NULL COMMENT 'Token autentikasi Wokwi',
                is_active    TINYINT(1)      NOT NULL DEFAULT 1,
                created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY uq_env_rooms_token (device_token),
                INDEX idx_env_rooms_zone_id (zone_id),
                CONSTRAINT fk_env_rooms_zone FOREIGN KEY (zone_id)
                    REFERENCES shared_zones (id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS env_rooms');
    }
};