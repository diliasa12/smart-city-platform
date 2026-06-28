<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
    ALTER TABLE env_room_telemetry_logs
        MODIFY ml_classification_status ENUM('nyaman', 'cukup_nyaman', 'tidak_nyaman') NULL,
        MODIFY predicted_next_busy_hour TINYINT UNSIGNED NULL,
        ADD COLUMN ml_status ENUM('pending', 'queued', 'done', 'failed') NOT NULL DEFAULT 'pending' AFTER decibel_level, --  Pastikan koma ini ada
        ADD COLUMN is_anomaly TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Normal, 1=Anomaly' AFTER predicted_next_busy_hour
");
    }

    public function down(): void
    {
        DB::unprepared("
            ALTER TABLE env_room_telemetry_logs
                DROP COLUMN ml_status,
                MODIFY ml_classification_status ENUM('nyaman', 'cukup_nyaman', 'tidak_nyaman') NOT NULL,
                MODIFY predicted_next_busy_hour TINYINT UNSIGNED NOT NULL
        ");
    }
};