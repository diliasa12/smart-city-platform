<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS users (
                id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                name       VARCHAR(255)    NOT NULL,
                email      VARCHAR(255)    NOT NULL,
                password   VARCHAR(255)    NOT NULL,
                phone      VARCHAR(25)         NULL,
                role       ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS users');
    }
};