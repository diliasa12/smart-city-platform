USE smartcity;

-- ============================================================
-- 1. SEED DATA: shared_oauth_clients
-- ============================================================
-- Menambahkan data dummy untuk client aplikasi Anda (Node.js Gateway & Laravel Frontend)
INSERT INTO shared_oauth_clients (client_id, client_secret, grant_types, redirect_uris, description, is_active)
VALUES 
(
    'nodejs_iot_gateway', 
    '$2y$10$e0MYzXyDxZ266a17bK3bEeFzo1Sg8K2f689HjklMNOpQrStUvWxyZ', -- Contoh Bcrypt / Hashed Secret
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
-- 2. SEED DATA: shared_oauth_tokens
-- ============================================================
-- Menambahkan contoh token aktif yang bisa langsung disimulasikan saat pengujian API
INSERT INTO shared_oauth_tokens (client_id, user_id, access_token, refresh_token, scope, expires_at)
VALUES 
(
    'nodejs_iot_gateway', 
    NULL, -- Machine-to-machine tidak membutuhkan User ID
    'mock_access_token_nodejs_gateway_xyz123', 
    'mock_refresh_token_nodejs_gateway_xyz123', 
    'telemetry:write', 
    DATE_ADD(NOW(), INTERVAL 30 DAY) -- Token valid selama 30 hari ke depan
),
(
    'laravel_web_dashboard', 
    1, -- Diasumsikan terikat ke user ID 1 (misal akun admin/internal)
    'mock_access_token_laravel_dashboard_abc789', 
    'mock_refresh_token_laravel_dashboard_abc789', 
    'telemetry:read', 
    DATE_ADD(NOW(), INTERVAL 1 DAY) -- Valid 1 hari
);


-- ============================================================
-- 3. SEED DATA: env_sensor_readings
-- ============================================================
-- Menambahkan data log telemetri ruangan (Suhu, Humidity, Kebisingan)
-- Disimulasikan untuk Room ID 1 (misal Ruang Belajar Utama) dengan kondisi fluktuatif beralur waktu
INSERT INTO env_sensor_readings (room_id, temperature, humidity, noise, created_at)
VALUES 
-- Log Jam 08:00 Pagi (Kondisi Ideal: Dingin, Lembab Pas, Sepi)
(1, 23.50, 50.00, 35.50, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Log Jam 09:00 Pagi (Mulai ada orang masuk: Suhu naik sedikit, mulai ada suara)
(1, 24.20, 52.50, 42.00, DATE_SUB(NOW(), INTERVAL 3 HOUR)),

-- Log Jam 10:00 Siang (Kondisi Ramai/Jam Sibuk: Agak gerah, bising karena diskusi)
(1, 26.80, 58.00, 65.20, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- Log Jam 11:00 Siang (AC diturunkan, tapi ruangan tetap bising)
(1, 25.00, 55.00, 60.10, DATE_SUB(NOW(), INTERVAL 1 HOUR)),

-- Log Kondisi Sekarang/Real-time (Kondisi Cukup Nyaman)
(1, 24.50, 53.00, 45.00, NOW());