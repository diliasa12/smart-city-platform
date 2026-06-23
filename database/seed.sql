USE smartcity;

-- ============================================================
-- KELOMPOK 0: DATA INDUK (Dibutuhkan agar Foreign Key tidak Error)
-- ============================================================

-- Menambahkan Zona/Wilayah terlebih dahulu
INSERT INTO shared_zones (id, name, city_district, coordinates, area_km2)
VALUES (1, 'Kampus Anggrek Binus', 'Jakarta Barat', '{"type": "Polygon", "coordinates": [[[106.7818, -6.2011], [106.7828, -6.2011], [106.7828, -6.2021], [106.7818, -6.2021], [106.7818, -6.2011]]]}', 0.5000);

-- Menambahkan Ruangan Terkait ke Zona 1
INSERT INTO env_rooms (id, zone_id, room_name, capacity, device_token, is_active)
VALUES (1, 1, 'Ruang Lab IoT Smt 3', 30, 'esp32-room1', 1);


-- ============================================================
-- KELOMPOK 1: SEED DATA - USERS (1 Admin, 2 User Biasa)
-- ============================================================
-- Menggunakan struktur tabel tunggal 'users' yang sudah dilengkapi kolom 'role' & 'phone'
-- Password berasal dari string 'password123' yang di-hash dengan bcryptjs (Salt Round 10)
INSERT INTO users (id, name, email, password, phone, role)
VALUES 
(
    1, 
    'Super Admin SmartCity', 
    'admin@smartcity.id', 
    '$2a$10$EixzaoOcyclpSy2wU6wecu5.289ZONC6vK28M2hFvRSL/5C76P2eq', -- Hash dari 'password123'
    '081234567890', 
    'admin'
),
(
    2, 
    'Budi Santoso', 
    'budi@gmail.com', 
    '$2a$10$EixzaoOcyclpSy2wU6wecu5.289ZONC6vK28M2hFvRSL/5C76P2eq', 
    '081299998888', 
    'user'
),
(
    3, 
    'Siti Aminah', 
    'siti@gmail.com', 
    '$2a$10$EixzaoOcyclpSy2wU6wecu5.289ZONC6vK28M2hFvRSL/5C76P2eq', 
    '081277776666', 
    'user'
);


-- ============================================================
-- KELOMPOK 2: SEED DATA - SHARED OAUTH CLIENTS
-- ============================================================
INSERT INTO shared_oauth_clients (client_id, client_secret, grant_types, redirect_uris, description, is_active)
VALUES 
(
    'nodejs_iot_gateway', 
    '$2y$10$e0MYzXyDxZ266a17bK3bEeFzo1Sg8K2f689HjklMNOpQrStUvWxyZ', 
    'client_credentials,refresh_token', 
    'http://localhost:3000/callback', 
    'Layanan Node.js Gateway penyerap data telemetri Wokwi', 
    1
),
(
    'laravel_web_dashboard', 
    '$2y$10$m9XyZzWvU87654321bK3bEeFzo1Sg8K2f689HjklMNOpQrStUvWabc', 
    'authorization_code,password,refresh_token', 
    'http://localhost:8000/oauth/callback', 
    'Aplikasi Web utama Laravel untuk dashboard publik customer', 
    1
);


-- ============================================================
-- KELOMPOK 3: SEED DATA - SHARED OAUTH TOKENS
-- ============================================================
INSERT INTO shared_oauth_tokens (client_id, user_id, access_token, refresh_token, scope, expires_at)
VALUES 
(
    'nodejs_iot_gateway', 
    NULL, 
    'mock_access_token_nodejs_gateway_xyz123', 
    'mock_refresh_token_nodejs_gateway_xyz123', 
    'telemetry:write', 
    DATE_ADD(NOW(), INTERVAL 30 DAY)
),
(
    'laravel_web_dashboard', 
    1, -- Terikat ke User ID 1 (Admin)
    'mock_access_token_laravel_dashboard_abc789', 
    'mock_refresh_token_laravel_dashboard_abc789', 
    'telemetry:read', 
    DATE_ADD(NOW(), INTERVAL 1 DAY)
);


-- ============================================================
-- KELOMPOK 4: SEED DATA - ENV ROOM TELEMETRY LOGS (Koreksi dari env_sensor_readings)
-- ============================================================
-- Disesuaikan dengan kolom asli: decibel_level, ml_classification_status, dan predicted_next_busy_hour
INSERT INTO env_room_telemetry_logs (room_id, temperature, humidity, decibel_level, ml_classification_status, predicted_next_busy_hour, created_at)
VALUES 
-- Log Jam 08:00 Pagi (Sepi & Nyaman)
(1, 23.50, 50.00, 35.50, 'nyaman', 10, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Log Jam 09:00 Pagi (Mulai terisi)
(1, 24.20, 52.50, 42.00, 'nyaman', 10, DATE_SUB(NOW(), INTERVAL 3 HOUR)),

-- Log Jam 10:00 Siang (Jam Sibuk, Bising & Mulai Gerah)
(1, 26.80, 58.00, 65.20, 'tidak_nyaman', 11, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- Log Jam 11:00 Siang (Cukup Nyaman)
(1, 25.00, 55.00, 60.10, 'cukup_nyaman', 12, DATE_SUB(NOW(), INTERVAL 1 HOUR)),

-- Log Kondisi Sekarang / Real-time
(1, 24.50, 53.00, 45.00, 'nyaman', 13, NOW());