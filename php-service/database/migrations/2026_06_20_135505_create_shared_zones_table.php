<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS shared_zones (
                id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                name          VARCHAR(100)    NOT NULL COMMENT 'Contoh: Kampus Anggrek, Wilayah Menteng',
                city_district VARCHAR(100)    NOT NULL COMMENT 'Contoh: Jakarta Barat, Jakarta Pusat',
                coordinates   JSON            NOT NULL COMMENT 'GeoJSON untuk pemetaan spasial',
                area_km2      DECIMAL(10, 4)  NOT NULL DEFAULT 0.0000,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY uq_zones_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS shared_zones');
    }
};