<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS room_bookings (
                id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                room_id     INT UNSIGNED    NOT NULL,
                user_id     INT UNSIGNED    NOT NULL,
                start_time  DATETIME        NOT NULL,
                end_time    DATETIME        NOT NULL,
                status      ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                INDEX idx_bookings_room_time (room_id, start_time, end_time),
                CONSTRAINT fk_bookings_room FOREIGN KEY (room_id)
                    REFERENCES env_rooms (id) ON DELETE CASCADE,
                CONSTRAINT fk_bookings_user FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS room_bookings');
    }
};