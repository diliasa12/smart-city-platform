
--  SmartCity Database Schema
--  Database  : smartcity
--  Engine    : MySQL 8.0+
--  Encoding  : utf8mb4 / utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS smartcity
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smartcity;


-- ----------------------------------------------------------
-- shared_oauth_clients
-- ----------------------------------------------------------
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
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='OAuth 2.0 registered clients';

-- ----------------------------------------------------------
-- shared_oauth_tokens
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS shared_oauth_tokens (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id     VARCHAR(128)    NOT NULL,
    user_id       INT UNSIGNED        NULL COMMENT 'NULL for machine-to-machine tokens',
    access_token  VARCHAR(512)    NOT NULL,
    refresh_token VARCHAR(512)        NULL,
    scope         VARCHAR(255)        NULL,
    expires_at    DATETIME        NOT NULL,
    revoked_at    DATETIME            NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_oauth_tokens_access  (access_token(255)),
    UNIQUE KEY uq_oauth_tokens_refresh (refresh_token(255)),
    INDEX idx_oauth_tokens_client_id  (client_id),
    INDEX idx_oauth_tokens_user_id    (user_id),
    INDEX idx_oauth_tokens_expires_at (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='OAuth 2.0 access and refresh tokens';

CREATE TABLE IF NOT EXISTS admin_accounts(
  id    INT UNSIGNED NOT NULL,
  name  VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(25) NULL,
  created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Akun admin';


CREATE TABLE IF NOT EXISTS room_telemetry_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id     INT UNSIGNED    NOT NULL,
    temperature DECIMAL(5, 2)       NULL COMMENT 'celsius',
    humidity    DECIMAL(5, 2)       NULL COMMENT 'percent',
    noise       DECIMAL(5, 2)       NULL COMMENT 'desibel',

   
    ml_classification_status ENUM('nyaman', 'cukup_nyaman', 'tidak_nyaman') NULL,
    predicted_next_busy_hour INT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id) 
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Kenyamanan ruang belajar';

-- ============================================================
-- SELESAI  –  schema.sql
-- ============================================================