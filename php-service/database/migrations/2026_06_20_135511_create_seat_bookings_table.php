<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS seat_bookings (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id      INT UNSIGNED NOT NULL,
                room_id      INT UNSIGNED NOT NULL,
                seat_number  VARCHAR(10)  NOT NULL,
                booking_date DATE         NOT NULL,
                start_time   TIME         NOT NULL,
                end_time     TIME         NOT NULL,
                status       ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (room_id) REFERENCES env_rooms(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS seat_bookings');
    }
};