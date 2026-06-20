<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS shared_oauth_tokens (
                id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_id     VARCHAR(128)    NOT NULL,
                user_id       INT UNSIGNED        NULL,
                access_token  TEXT            NOT NULL COMMENT 'Aman untuk JWT berukuran panjang',
                refresh_token TEXT                NULL,
                scope         VARCHAR(255)        NULL,
                expires_at    DATETIME        NOT NULL,
                revoked_at    DATETIME            NULL,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                INDEX idx_oauth_tokens_client_id  (client_id),
                INDEX idx_oauth_tokens_user_id    (user_id),
                INDEX idx_oauth_tokens_expires_at (expires_at),
                UNIQUE KEY uq_oauth_tokens_access  (access_token(255)),
                UNIQUE KEY uq_oauth_tokens_refresh (refresh_token(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS shared_oauth_tokens');
    }
};