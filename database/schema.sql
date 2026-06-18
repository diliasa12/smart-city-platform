-- ============================================================
--  SmartCity Database Schema (Complete & Refined)
--  Database  : smartcity
--  Engine    : MySQL 8.0+
--  Encoding  : utf8mb4 / utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS smartcity
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smartcity;

-- ============================================================
-- SECTION 1: SHARED & INFRASTRUCTURE SERVICES
-- ============================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_accounts (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    password   VARCHAR(255)    NOT NULL COMMENT 'Hashed password Bcrypt',
    phone      VARCHAR(25)         NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    password   VARCHAR(255)    NOT NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION 2: ENVIRONMENT & SMART ROOM SERVICES
-- ============================================================

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
    CONSTRAINT fk_env_rooms_zone FOREIGN KEY (zone_id) REFERENCES shared_zones (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES env_rooms (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS env_room_telemetry_logs (
    id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id                   INT UNSIGNED    NOT NULL,
    temperature               DECIMAL(5, 2)   NOT NULL COMMENT 'Data Mikro Indoor',
    humidity                  DECIMAL(5, 2)   NOT NULL COMMENT 'Data Mikro Indoor',
    decibel_level             DECIMAL(5, 2)   NOT NULL COMMENT 'Data Kebisingan Mikro',
    ml_classification_status  ENUM('nyaman', 'cukup_nyaman', 'tidak_nyaman') NOT NULL,
    predicted_next_busy_hour  TINYINT UNSIGNED NOT NULL,
    created_at                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_room_telemetry_room_id (room_id),
    INDEX idx_room_telemetry_created_at (created_at),
    INDEX idx_room_telemetry_latest (room_id, created_at DESC), 
    CONSTRAINT fk_room_telemetry_room FOREIGN KEY (room_id) REFERENCES env_rooms (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS env_sensor_readings (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    zone_id     INT UNSIGNED    NOT NULL COMMENT 'Lokasi Makro Outdoor',
    pm25        DECIMAL(8, 3)       NULL,
    pm10        DECIMAL(8, 3)       NULL,
    no2         DECIMAL(8, 3)       NULL,
    co          DECIMAL(8, 3)       NULL,
    o3          DECIMAL(8, 3)       NULL,
    temperature DECIMAL(5, 2)       NULL COMMENT 'Suhu Makro Luar Ruangan',
    humidity    DECIMAL(5, 2)       NULL COMMENT 'Kelembapan Makro Luar Ruangan',
    sensor_id   VARCHAR(100)        NULL,
    recorded_at DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_env_readings_zone_id     (zone_id),
    INDEX idx_env_readings_recorded_at (recorded_at),
    CONSTRAINT fk_env_readings_zone FOREIGN KEY (zone_id) REFERENCES shared_zones (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_commands_room FOREIGN KEY (room_id) REFERENCES env_rooms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;