-- ============================================================
--  SmartCity Database Schema
--  Database  : smartcity
--  Engine    : MySQL 8.0+
--  Encoding  : utf8mb4 / utf8mb4_unicode_ci
--
--  Table prefix per service:
--    citizen_   → Citizen Service
--    traffic_   → Traffic Service
--    env_       → Environment Service
--    shared_    → Shared / cross-service
--
--  MySQL users (created at the bottom):
--    svc_citizen   → citizen_* tables only
--    svc_traffic   → traffic_* tables only
--    svc_env       → env_* tables only
--    svc_readonly  → SELECT on all tables (monitoring/reporting)
-- ============================================================

CREATE DATABASE IF NOT EXISTS smartcity
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smartcity;

-- ============================================================
-- SHARED SERVICE TABLES
-- ============================================================

-- ----------------------------------------------------------
-- shared_zones
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS shared_zones (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name         VARCHAR(100)    NOT NULL,
    city_district VARCHAR(100)   NOT NULL,
    coordinates  JSON            NOT NULL COMMENT 'GeoJSON polygon or point',
    area_km2     DECIMAL(10, 4)  NOT NULL DEFAULT 0.0000,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_zones_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Administrative zones used across all services';

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


-- ============================================================
-- CITIZEN SERVICE TABLES
-- ============================================================

-- ----------------------------------------------------------
-- citizen_citizens
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS citizen_citizens (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nik        CHAR(16)        NOT NULL COMMENT 'Nomor Induk Kependudukan (16 digits)',
    name       VARCHAR(150)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    phone      VARCHAR(20)         NULL,
    zone_id    INT UNSIGNED    NOT NULL,
    role       ENUM('citizen','admin','officer') NOT NULL DEFAULT 'citizen',
    password   VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash',
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_citizens_nik   (nik),
    UNIQUE KEY uq_citizens_email (email),
    INDEX idx_citizens_zone_id   (zone_id),
    INDEX idx_citizens_role      (role),
    CONSTRAINT fk_citizens_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registered citizens / system users';

-- ----------------------------------------------------------
-- citizen_reports
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS citizen_reports (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    citizen_id  INT UNSIGNED    NOT NULL,
    category    ENUM(
                    'infrastructure',
                    'environment',
                    'traffic',
                    'public_safety',
                    'other'
                )               NOT NULL DEFAULT 'other',
    description TEXT            NOT NULL,
    zone_id     INT UNSIGNED    NOT NULL,
    status      ENUM('pending','in_progress','resolved','rejected')
                                NOT NULL DEFAULT 'pending',
    attachment_url VARCHAR(512)     NULL,
    resolved_at DATETIME            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_reports_citizen_id  (citizen_id),
    INDEX idx_reports_zone_id     (zone_id),        -- sering di-filter
    INDEX idx_reports_status      (status),         -- sering di-filter
    INDEX idx_reports_category    (category),
    INDEX idx_reports_created_at  (created_at),
    CONSTRAINT fk_reports_citizen
        FOREIGN KEY (citizen_id) REFERENCES citizen_citizens (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reports_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Citizen-submitted reports / complaints';

-- ----------------------------------------------------------
-- citizen_notifications
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS citizen_notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    citizen_id  INT UNSIGNED    NOT NULL,
    title       VARCHAR(255)    NOT NULL,
    body        TEXT            NOT NULL,
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    read_at     DATETIME            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_notifications_citizen_id (citizen_id),
    INDEX idx_notifications_is_read    (is_read),
    INDEX idx_notifications_created_at (created_at),
    CONSTRAINT fk_notifications_citizen
        FOREIGN KEY (citizen_id) REFERENCES citizen_citizens (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Push / in-app notifications per citizen';


-- ============================================================
-- TRAFFIC SERVICE TABLES
-- ============================================================

-- ----------------------------------------------------------
-- traffic_readings
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS traffic_readings (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    zone_id         INT UNSIGNED    NOT NULL,
    vehicle_density INT UNSIGNED    NOT NULL COMMENT 'vehicles per km',
    avg_speed_kmh   DECIMAL(6, 2)   NOT NULL DEFAULT 0.00,
    incident_flag   TINYINT(1)      NOT NULL DEFAULT 0,
    sensor_source   VARCHAR(100)        NULL COMMENT 'sensor or camera ID',
    recorded_at     DATETIME        NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_traffic_readings_zone_id     (zone_id),        -- sering di-filter
    INDEX idx_traffic_readings_recorded_at (recorded_at),    -- sering di-filter
    INDEX idx_traffic_readings_incident    (incident_flag),
    -- Composite untuk query zone + waktu (paling umum)
    INDEX idx_traffic_readings_zone_time   (zone_id, recorded_at),
    CONSTRAINT fk_traffic_readings_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Periodic traffic sensor readings per zone';

-- ----------------------------------------------------------
-- traffic_incidents
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS traffic_incidents (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    zone_id     INT UNSIGNED    NOT NULL,
    type        ENUM('accident','congestion','road_closure','hazard','other')
                                NOT NULL DEFAULT 'other',
    severity    ENUM('low','medium','high','critical')
                                NOT NULL DEFAULT 'low',
    description TEXT                NULL,
    resolved_at DATETIME            NULL,
    reported_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_traffic_incidents_zone_id     (zone_id),        -- sering di-filter
    INDEX idx_traffic_incidents_reported_at (reported_at),
    INDEX idx_traffic_incidents_severity    (severity),
    INDEX idx_traffic_incidents_type        (type),
    INDEX idx_traffic_incidents_resolved_at (resolved_at),
    CONSTRAINT fk_traffic_incidents_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Traffic incidents reported per zone';


-- ============================================================
-- ENVIRONMENT SERVICE TABLES
-- ============================================================

-- ----------------------------------------------------------
-- env_sensor_readings
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS env_sensor_readings (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    zone_id     INT UNSIGNED    NOT NULL,
    pm25        DECIMAL(8, 3)       NULL COMMENT 'µg/m³',
    pm10        DECIMAL(8, 3)       NULL COMMENT 'µg/m³',
    no2         DECIMAL(8, 3)       NULL COMMENT 'ppb',
    co          DECIMAL(8, 3)       NULL COMMENT 'ppm',
    o3          DECIMAL(8, 3)       NULL COMMENT 'ppb',
    temperature DECIMAL(5, 2)       NULL COMMENT 'Celsius',
    humidity    DECIMAL(5, 2)       NULL COMMENT 'percent',
    sensor_id   VARCHAR(100)        NULL,
    recorded_at DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_env_readings_zone_id     (zone_id),        -- sering di-filter
    INDEX idx_env_readings_recorded_at (recorded_at),    -- sering di-filter
    -- Composite untuk query analitik zone + waktu
    INDEX idx_env_readings_zone_time   (zone_id, recorded_at),
    CONSTRAINT fk_env_readings_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Environmental sensor readings (air quality, weather)';

-- ----------------------------------------------------------
-- env_alerts
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS env_alerts (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    zone_id     INT UNSIGNED    NOT NULL,
    alert_type  ENUM('pm25','pm10','no2','co','o3','temperature','humidity','general')
                                NOT NULL DEFAULT 'general',
    severity    ENUM('info','warning','danger','critical')
                                NOT NULL DEFAULT 'info',
    value       DECIMAL(10, 3)  NOT NULL COMMENT 'Measured value that triggered alert',
    threshold   DECIMAL(10, 3)  NOT NULL COMMENT 'Threshold value that was breached',
    message     VARCHAR(500)        NULL,
    resolved_at DATETIME            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_env_alerts_zone_id    (zone_id),        -- sering di-filter
    INDEX idx_env_alerts_severity   (severity),
    INDEX idx_env_alerts_alert_type (alert_type),
    INDEX idx_env_alerts_created_at (created_at),
    INDEX idx_env_alerts_resolved   (resolved_at),
    CONSTRAINT fk_env_alerts_zone
        FOREIGN KEY (zone_id) REFERENCES shared_zones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Environmental threshold alerts per zone';


-- ============================================================
-- MYSQL USERS & GRANTS
-- ============================================================
-- Jalankan bagian ini sebagai root / superuser MySQL.
-- Ganti 'StrongPassword!X' dengan password yang aman di production.
-- '%' bisa dibatasi ke host aplikasi tertentu (misal '10.0.0.%').
-- ============================================================

-- Citizen Service user
CREATE USER IF NOT EXISTS 'svc_citizen'@'%'
    IDENTIFIED BY 'CitizenSvc#2024!';

GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.citizen_citizens      TO 'svc_citizen'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.citizen_reports       TO 'svc_citizen'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.citizen_notifications TO 'svc_citizen'@'%';
-- Read-only akses ke shared tables
GRANT SELECT ON smartcity.shared_zones         TO 'svc_citizen'@'%';
GRANT SELECT ON smartcity.shared_oauth_clients TO 'svc_citizen'@'%';
GRANT SELECT ON smartcity.shared_oauth_tokens  TO 'svc_citizen'@'%';

-- Traffic Service user
CREATE USER IF NOT EXISTS 'svc_traffic'@'%'
    IDENTIFIED BY 'TrafficSvc#2024!';

GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.traffic_readings   TO 'svc_traffic'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.traffic_incidents  TO 'svc_traffic'@'%';
-- Read-only akses ke shared tables
GRANT SELECT ON smartcity.shared_zones         TO 'svc_traffic'@'%';
GRANT SELECT ON smartcity.shared_oauth_clients TO 'svc_traffic'@'%';
GRANT SELECT ON smartcity.shared_oauth_tokens  TO 'svc_traffic'@'%';

-- Environment Service user
CREATE USER IF NOT EXISTS 'svc_env'@'%'
    IDENTIFIED BY 'EnvSvc#2024!';

GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.env_sensor_readings TO 'svc_env'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON smartcity.env_alerts          TO 'svc_env'@'%';
-- Read-only akses ke shared tables
GRANT SELECT ON smartcity.shared_zones         TO 'svc_env'@'%';
GRANT SELECT ON smartcity.shared_oauth_clients TO 'svc_env'@'%';
GRANT SELECT ON smartcity.shared_oauth_tokens  TO 'svc_env'@'%';

-- Read-only user untuk monitoring / reporting / BI
CREATE USER IF NOT EXISTS 'svc_readonly'@'%'
    IDENTIFIED BY 'ReadOnly#2024!';

GRANT SELECT ON smartcity.* TO 'svc_readonly'@'%';

-- Terapkan perubahan privilege
FLUSH PRIVILEGES;

-- ============================================================
-- SELESAI  –  schema.sql
-- ============================================================