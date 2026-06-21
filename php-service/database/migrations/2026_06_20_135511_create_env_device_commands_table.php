<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS env_device_commands (
                id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                room_id      INT UNSIGNED    NOT NULL,
                command_type VARCHAR(50)     NOT NULL,
                payload      JSON            NOT NULL,
                status       ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
                created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                executed_at  DATETIME            NULL,

                PRIMARY KEY (id),
                INDEX idx_commands_lookup (room_id, status),
                CONSTRAINT fk_commands_room FOREIGN KEY (room_id)
                    REFERENCES env_rooms (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS env_device_commands');
    }
};