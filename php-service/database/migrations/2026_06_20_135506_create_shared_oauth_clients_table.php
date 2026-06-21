<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS shared_oauth_clients (
                id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                client_id     VARCHAR(128)    NOT NULL,
                client_secret VARCHAR(256)    NOT NULL,
                grant_types   SET('authorization_code','client_credentials','refresh_token','password')
                                              NOT NULL DEFAULT 'authorization_code',
                redirect_uris TEXT            NOT NULL,
                description   VARCHAR(255)        NULL,
                is_active     TINYINT(1)      NOT NULL DEFAULT 1,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY uq_oauth_clients_client_id (client_id),
                INDEX idx_oauth_clients_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS shared_oauth_clients');
    }
};