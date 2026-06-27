USE smartcity;
-- Password semua user: 'password123' (bcryptjs Node.js, salt round 10, prefix $2b$)

-- ============================================================
-- ZONES
-- ============================================================
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (1,'Kampus Anggrek Binus','Jakarta Barat','{"type":"Polygon","coordinates":[[[106.78,-6.2],[106.79,-6.2],[106.79,-6.21],[106.78,-6.21],[106.78,-6.2]]]}',0.5) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (2,'Kampus Syahdan Binus','Jakarta Barat','{"type":"Polygon","coordinates":[[[106.75,-6.19],[106.76,-6.19],[106.76,-6.2],[106.75,-6.2],[106.75,-6.19]]]}',0.35) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (3,'Kampus Kijang Binus','Jakarta Barat','{"type":"Polygon","coordinates":[[[106.76,-6.21],[106.77,-6.21],[106.77,-6.22],[106.76,-6.22],[106.76,-6.21]]]}',0.28) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (4,'Wilayah Menteng','Jakarta Pusat','{"type":"Polygon","coordinates":[[[106.83,-6.19],[106.84,-6.19],[106.84,-6.2],[106.83,-6.2],[106.83,-6.19]]]}',1.2) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (5,'Wilayah Sudirman','Jakarta Selatan','{"type":"Polygon","coordinates":[[[106.81,-6.22],[106.82,-6.22],[106.82,-6.23],[106.81,-6.23],[106.81,-6.22]]]}',2.1) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (6,'Wilayah Kelapa Gading','Jakarta Utara','{"type":"Polygon","coordinates":[[[106.9,-6.16],[106.91,-6.16],[106.91,-6.17],[106.9,-6.17],[106.9,-6.16]]]}',1.8) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (7,'Wilayah Kemang','Jakarta Selatan','{"type":"Polygon","coordinates":[[[106.8,-6.26],[106.81,-6.26],[106.81,-6.27],[106.8,-6.27],[106.8,-6.26]]]}',0.95) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (8,'Wilayah BSD City','Tangerang Selatan','{"type":"Polygon","coordinates":[[[106.64,-6.3],[106.65,-6.3],[106.65,-6.31],[106.64,-6.31],[106.64,-6.3]]]}',3.5) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (9,'Wilayah Alam Sutera','Tangerang Selatan','{"type":"Polygon","coordinates":[[[106.63,-6.29],[106.64,-6.29],[106.64,-6.3],[106.63,-6.3],[106.63,-6.29]]]}',2.7) ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO shared_zones (id,name,city_district,coordinates,area_km2) VALUES (10,'Wilayah Depok Margonda','Depok','{"type":"Polygon","coordinates":[[[106.82,-6.38],[106.83,-6.38],[106.83,-6.39],[106.82,-6.39],[106.82,-6.38]]]}',1.6) ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ============================================================
-- USERS  (password: 'password123', hashed with bcryptjs $2b$10$)
-- ============================================================
INSERT INTO users (id,name,email,password,phone,role) VALUES (1,'Super Admin SmartCity','admin@smartcity.id','$2b$10$gn20X4ovH2P4FzzWIAGmdeL1jeVqRNzq4o5gh2EL3pglLDTvqigCK','081234567890','admin') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (2,'Budi Santoso','budi@gmail.com','$2b$10$RcZ9RyGU/9FRVBU2x9QZT.QuxhIOZe83otfUFW300UJxFAvPO8Ug6','081299998888','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (3,'Siti Aminah','siti@gmail.com','$2b$10$1oasF2PSEaoBiCuY1JaRdebncpSr1QZqvZEJlIJLGbe7uvBgnvqPK','081277776666','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (4,'Reza Pratama','reza@gmail.com','$2b$10$J3o/GcAV0v4zvmAto6mDKOD514r1.aq2WgZgN8rU6LQadcXGoq0FS','081211112222','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (5,'Dewi Lestari','dewi@gmail.com','$2b$10$5.a0KBakethoatxz9f4FfenbGsL9k/C6goqtzHAz8KFMazVcZMiFm','081233334444','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (6,'Andi Wijaya','andi@gmail.com','$2b$10$MaWsm/uv95ryWdqMUwqhE.PUiSikNRfHFnzl8BLHmJbYG9EkF0yn6','081255556666','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (7,'Nurul Hidayah','nurul@gmail.com','$2b$10$lVWsGuxNuKdEEDbkcayiv.cv87g3Q6fWsQo9kHOYjsAdOY31m5Uta','081277778888','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (8,'Fajar Setiawan','fajar@gmail.com','$2b$10$vSEZKRe0.UEQreZtShsJIu4u2b8skD2lsoZu5mDYnE7NLkI/Jm8Ee','081299990000','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (9,'Maya Putri','maya@gmail.com','$2b$10$svD0sW2kVF/MgQeQiUHJ0e0GkbR0AsCPTPFl9Ux4JdhFjdDOwxOwS','081211113333','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (10,'Rizky Firmansyah','rizky@gmail.com','$2b$10$AzHfMbFZLorIAo6vHYJNL.xBwBhSWLvDdM2m.10NIAQtQpd8TCLyC','081244445555','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (11,'Hana Kusuma','hana@gmail.com','$2b$10$7uIhKHB.Jg2N0TUMTbwRxuSh7VQw24nlv2OFL11Dg.XjfbhNsNueW','081266667777','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (12,'Dimas Arya','dimas@gmail.com','$2b$10$WMyivRUW.XUiYXhGYXK5fOYBppwdtijrCFRfcqRI.1aQ2BDFQPYoC','081288889999','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (13,'Larasati Putri','laras@gmail.com','$2b$10$nBNdKaF8t4w.vPmEKQL8pu.ZYzauTb598nWaRq3XOcsd4CGPubnn2','081211114444','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (14,'Bagas Prabowo','bagas@gmail.com','$2b$10$55STgXh6IL3D09nJ3VHHAehdgksq62l/ABrIzNzN2KH70Tm4wLR0u','081233335555','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (15,'Citra Dewi','citra@gmail.com','$2b$10$EVN5Ogl6j0XT0CRqKBnOnelxxehIs205h8esYH1zXysqXsxlk86RK','081255556677','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (16,'Fauzan Hakim','fauzan@gmail.com','$2b$10$VqmI98X4SGEo33eePrWM3uMH.knkZ6RP.pmrK9VoWyjdGxWLWXIOG','081277778899','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (17,'Gita Nuraini','gita@gmail.com','$2b$10$OqLZyvMSm0mZKGgtboGxy.38zFHXuEfpCXfvPFbp98rLbeR3TzjkC','081299990011','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (18,'Hendra Saputra','hendra@gmail.com','$2b$10$FiAIaj2ly/m1KSISCjQ6XOF2A81oip6b5Un9LquoICJ7ELCapvuga','081211112233','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (19,'Indah Permata','indah@gmail.com','$2b$10$2TzvWg6BXZK6.TaB23o4zumV.FESNS2RYrYsN0SO.ndWQxi8FqyZK','081233334455','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);
INSERT INTO users (id,name,email,password,phone,role) VALUES (20,'Joko Santoso','joko@gmail.com','$2b$10$D4iFHB5j099dYEnXjeaBu.zFh2nq6vIc8deYtBYf1Y2sW4EQvwa6W','081255556688','user') ON DUPLICATE KEY UPDATE password=VALUES(password),phone=VALUES(phone);

-- ============================================================
-- ENV_ROOMS
-- ============================================================
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (1,1,'Ruang Lab IoT Smt 3',30,'esp32-room1',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (2,1,'Ruang Kelas A201',40,'esp32-room2',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (3,1,'Ruang Kelas A301',35,'esp32-room3',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (4,2,'Ruang Lab Komputer B1',25,'esp32-room4',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (5,2,'Ruang Seminar Syahdan',80,'esp32-room5',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (6,3,'Ruang Meeting Kijang 1',15,'esp32-room6',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (7,3,'Ruang Kelas K201',40,'esp32-room7',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (8,4,'Ruang Co-Working Menteng',50,'esp32-room8',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (9,5,'Ruang Konferensi Sudirman',60,'esp32-room9',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (10,5,'Ruang Kerja Bersama Sudirman',45,'esp32-room10',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (11,6,'Ruang Rapat Kelapa Gading',20,'esp32-room11',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (12,7,'Ruang Studio Kemang',30,'esp32-room12',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (13,8,'Ruang Inovasi BSD',55,'esp32-room13',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (14,9,'Ruang Belajar Alam Sutera',35,'esp32-room14',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);
INSERT INTO env_rooms (id,zone_id,room_name,capacity,device_token,is_active) VALUES (15,10,'Ruang Diskusi Margonda',25,'esp32-room15',1) ON DUPLICATE KEY UPDATE room_name=VALUES(room_name);

-- ============================================================
-- SHARED_OAUTH_CLIENTS
-- ============================================================
INSERT INTO shared_oauth_clients (client_id,client_secret,grant_types,redirect_uris,description,is_active) VALUES ('nodejs_iot_gateway','$2y$10$e0MYzXyDxZ266a17bK3bEeFzo1Sg8K2f689HjklMNOpQrStUvWxyZ','client_credentials,refresh_token','http://localhost:3000/callback','Node.js Gateway telemetri Wokwi',1) ON DUPLICATE KEY UPDATE description=VALUES(description);
INSERT INTO shared_oauth_clients (client_id,client_secret,grant_types,redirect_uris,description,is_active) VALUES ('laravel_web_dashboard','$2y$10$m9XyZzWvU87654321bK3bEeFzo1Sg8K2f689HjklMNOpQrStUvWabc','authorization_code,password,refresh_token','http://localhost:8000/oauth/callback','Laravel Web Dashboard',1) ON DUPLICATE KEY UPDATE description=VALUES(description);

-- ============================================================
-- SHARED_OAUTH_TOKENS
-- ============================================================
INSERT INTO shared_oauth_tokens (client_id,user_id,access_token,refresh_token,scope,expires_at) VALUES ('nodejs_iot_gateway',NULL,'mock_access_token_nodejs_gateway_xyz123','mock_refresh_token_nodejs_gateway_xyz123','telemetry:write',DATE_ADD(NOW(),INTERVAL 30 DAY)) ON DUPLICATE KEY UPDATE expires_at=VALUES(expires_at);
INSERT INTO shared_oauth_tokens (client_id,user_id,access_token,refresh_token,scope,expires_at) VALUES ('laravel_web_dashboard',1,'mock_access_token_laravel_dashboard_abc789','mock_refresh_token_laravel_dashboard_abc789','telemetry:read',DATE_ADD(NOW(),INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expires_at=VALUES(expires_at);

-- ============================================================
-- ENV_ROOM_TELEMETRY_LOGS (90 rows, 6 per ruangan)
-- ============================================================
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,29.42,52.35,36.98,'done','tidak_nyaman',9,'2026-06-26 13:26:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,27.9,45.95,34.68,'done','cukup_nyaman',11,'2026-06-24 06:22:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,22.27,50.97,62.49,'done','cukup_nyaman',16,'2026-06-24 10:49:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,26.49,53.35,73.47,'done','tidak_nyaman',20,'2026-06-24 22:13:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,26.23,53.34,40.77,'done','cukup_nyaman',20,'2026-06-26 06:43:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (1,22.93,47.9,72.37,'done','tidak_nyaman',17,'2026-06-25 08:21:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,22.43,58.78,36.24,'done','nyaman',14,'2026-06-25 17:36:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,24.93,63.86,74.27,'done','tidak_nyaman',13,'2026-06-26 16:52:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,22.7,64.84,68.65,'done','tidak_nyaman',9,'2026-06-26 02:42:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,23.01,53.34,61.78,'done','cukup_nyaman',13,'2026-06-25 21:32:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,25.55,65.11,65.09,'done','tidak_nyaman',18,'2026-06-26 07:04:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (2,28.35,61.02,42.24,'done','cukup_nyaman',15,'2026-06-26 17:49:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,31.9,64.2,57.85,'done','tidak_nyaman',18,'2026-06-25 03:10:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,29.68,46.68,71.09,'done','tidak_nyaman',20,'2026-06-25 09:34:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,24.68,51.33,77.15,'done','tidak_nyaman',19,'2026-06-25 11:02:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,28.55,56.87,75.73,'done','tidak_nyaman',15,'2026-06-25 11:14:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,23.4,67.35,56.95,'done','cukup_nyaman',19,'2026-06-26 09:11:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (3,27.84,55.86,79.87,'done','tidak_nyaman',10,'2026-06-24 20:30:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,22.91,46.41,35.48,'done','nyaman',18,'2026-06-24 09:56:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,28.8,62.89,49.24,'done','cukup_nyaman',17,'2026-06-26 06:37:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,24.51,61.6,77.12,'done','tidak_nyaman',18,'2026-06-24 15:54:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,30.85,67.53,68.43,'done','tidak_nyaman',13,'2026-06-26 12:44:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,26.35,58.61,77.69,'done','tidak_nyaman',19,'2026-06-26 13:09:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (4,29.62,60.23,35.32,'done','tidak_nyaman',18,'2026-06-25 17:55:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,28.39,63.27,37.64,'done','cukup_nyaman',20,'2026-06-25 12:34:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,31.53,72.67,75.92,'done','tidak_nyaman',17,'2026-06-26 06:53:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,22.19,72.87,73.94,'done','tidak_nyaman',20,'2026-06-25 09:56:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,22.58,71.34,77.35,'done','tidak_nyaman',9,'2026-06-25 12:12:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,22.69,67.82,68.29,'done','tidak_nyaman',10,'2026-06-24 12:35:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (5,23.65,60.83,60.33,'done','cukup_nyaman',11,'2026-06-24 14:52:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,29.3,51.03,45.59,'done','tidak_nyaman',18,'2026-06-24 05:39:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,31.0,58.54,42.4,'done','tidak_nyaman',9,'2026-06-25 03:59:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,27.88,51.9,41.01,'done','cukup_nyaman',9,'2026-06-25 08:26:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,22.67,45.94,46.52,'done','nyaman',16,'2026-06-26 20:13:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,28.69,51.43,36.62,'done','cukup_nyaman',17,'2026-06-25 21:10:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (6,29.85,69.22,39.52,'done','tidak_nyaman',9,'2026-06-24 15:12:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,26.24,59.01,66.45,'done','tidak_nyaman',18,'2026-06-24 20:05:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,26.03,55.18,73.08,'done','tidak_nyaman',11,'2026-06-26 15:24:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,27.36,49.21,39.17,'done','cukup_nyaman',15,'2026-06-26 03:15:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,31.23,58.29,73.07,'done','tidak_nyaman',16,'2026-06-25 19:32:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,28.52,61.22,30.74,'done','cukup_nyaman',9,'2026-06-26 15:24:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (7,26.06,59.44,73.23,'done','tidak_nyaman',8,'2026-06-25 21:17:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,22.02,56.71,76.33,'done','tidak_nyaman',20,'2026-06-26 06:03:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,26.23,73.72,79.77,'done','tidak_nyaman',16,'2026-06-24 17:09:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,23.9,51.53,32.92,'done','nyaman',19,'2026-06-24 13:18:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,29.48,46.71,59.21,'done','tidak_nyaman',16,'2026-06-24 06:24:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,22.57,60.23,72.57,'done','tidak_nyaman',9,'2026-06-24 08:17:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (8,30.62,57.11,77.08,'done','tidak_nyaman',17,'2026-06-26 18:44:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,27.95,63.58,50.96,'done','cukup_nyaman',17,'2026-06-25 19:50:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,25.16,52.82,63.49,'done','cukup_nyaman',13,'2026-06-24 02:54:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,25.96,65.15,45.0,'done','nyaman',13,'2026-06-25 21:11:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,26.58,74.95,79.8,'done','tidak_nyaman',9,'2026-06-26 18:27:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,27.06,48.97,47.45,'done','cukup_nyaman',9,'2026-06-24 07:14:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (9,24.85,58.15,57.16,'done','cukup_nyaman',12,'2026-06-25 20:04:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,28.68,61.64,76.59,'done','tidak_nyaman',9,'2026-06-24 08:27:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,23.15,48.21,57.66,'done','cukup_nyaman',12,'2026-06-26 10:11:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,24.11,55.29,64.37,'done','cukup_nyaman',12,'2026-06-25 14:49:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,24.51,72.24,32.54,'done','nyaman',18,'2026-06-24 10:56:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,24.77,45.11,68.56,'done','tidak_nyaman',18,'2026-06-24 20:34:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (10,29.41,61.55,51.38,'done','tidak_nyaman',8,'2026-06-25 18:17:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,31.45,65.73,37.45,'done','tidak_nyaman',8,'2026-06-26 13:23:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,27.53,57.89,32.09,'done','cukup_nyaman',13,'2026-06-25 03:50:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,25.58,65.46,63.35,'done','cukup_nyaman',13,'2026-06-26 21:30:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,30.75,74.22,67.47,'done','tidak_nyaman',11,'2026-06-24 03:31:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,30.11,71.44,31.24,'done','tidak_nyaman',19,'2026-06-26 06:36:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (11,31.31,69.07,73.2,'done','tidak_nyaman',20,'2026-06-25 08:37:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,23.59,66.04,49.13,'done','nyaman',8,'2026-06-25 20:10:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,24.0,72.55,47.48,'done','nyaman',20,'2026-06-24 15:13:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,22.24,50.79,46.41,'done','nyaman',9,'2026-06-25 22:13:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,28.41,56.99,79.06,'done','tidak_nyaman',16,'2026-06-25 16:05:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,23.15,74.11,38.93,'done','nyaman',12,'2026-06-25 09:26:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (12,27.97,55.37,69.32,'done','tidak_nyaman',14,'2026-06-26 23:21:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,25.85,62.3,42.74,'done','nyaman',19,'2026-06-24 10:20:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,27.2,69.2,64.34,'done','cukup_nyaman',19,'2026-06-24 20:27:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,26.31,73.47,76.04,'done','tidak_nyaman',17,'2026-06-26 02:04:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,30.48,66.59,45.02,'done','tidak_nyaman',12,'2026-06-25 10:45:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,26.02,53.87,36.36,'done','cukup_nyaman',14,'2026-06-24 23:07:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (13,29.48,50.22,58.46,'done','tidak_nyaman',14,'2026-06-25 02:44:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,22.0,53.61,51.49,'done','cukup_nyaman',17,'2026-06-24 04:34:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,26.42,65.27,55.56,'done','cukup_nyaman',20,'2026-06-25 09:58:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,22.85,60.46,61.65,'done','cukup_nyaman',13,'2026-06-26 05:45:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,31.52,52.05,45.52,'done','tidak_nyaman',20,'2026-06-26 15:35:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,22.24,52.35,53.76,'done','cukup_nyaman',20,'2026-06-26 02:18:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (14,26.14,63.89,39.72,'done','cukup_nyaman',19,'2026-06-26 17:58:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,26.0,49.43,64.38,'done','cukup_nyaman',20,'2026-06-25 01:56:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,26.25,50.28,77.9,'done','tidak_nyaman',16,'2026-06-26 13:38:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,27.57,72.52,36.07,'done','cukup_nyaman',10,'2026-06-24 16:24:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,27.31,61.77,45.86,'done','cukup_nyaman',20,'2026-06-24 15:45:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,30.15,71.76,51.34,'done','tidak_nyaman',16,'2026-06-24 18:48:42');
INSERT INTO env_room_telemetry_logs (room_id,temperature,humidity,decibel_level,ml_status,ml_classification_status,predicted_next_busy_hour,created_at) VALUES (15,23.59,70.84,52.5,'done','cukup_nyaman',20,'2026-06-24 17:30:42');

-- ============================================================
-- ENV_SENSOR_READINGS (30 rows)
-- ============================================================
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (4,67.99,40.504,0.119,1.263,0.027,29.4,74.97,'sensor-zone4-01','2026-06-24 15:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (5,30.189,108.233,0.021,0.462,0.037,26.53,57.49,'sensor-zone5-02','2026-06-22 17:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (7,29.816,61.252,0.019,2.099,0.037,32.7,83.11,'sensor-zone7-03','2026-06-26 22:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (10,33.529,10.649,0.059,1.908,0.07,34.53,64.67,'sensor-zone10-04','2026-06-21 08:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (10,72.336,63.705,0.048,1.266,0.037,31.69,77.93,'sensor-zone10-05','2026-06-25 09:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (8,73.966,117.879,0.085,2.277,0.051,31.63,52.94,'sensor-zone8-06','2026-06-22 14:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (3,70.014,29.99,0.046,0.886,0.042,28.38,80.79,'sensor-zone3-07','2026-06-24 04:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (7,23.92,19.01,0.013,1.395,0.08,28.5,72.76,'sensor-zone7-08','2026-06-20 05:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (1,61.567,114.457,0.038,0.149,0.021,26.26,73.43,'sensor-zone1-09','2026-06-21 03:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (4,39.878,38.187,0.062,1.554,0.077,32.18,77.22,'sensor-zone4-10','2026-06-25 10:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (5,13.107,12.825,0.054,1.726,0.077,28.97,75.03,'sensor-zone5-11','2026-06-26 08:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (10,56.796,78.997,0.024,1.954,0.07,31.0,54.24,'sensor-zone10-12','2026-06-21 03:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (1,31.04,57.122,0.062,1.314,0.034,33.5,78.78,'sensor-zone1-13','2026-06-26 00:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (7,77.059,79.914,0.126,1.798,0.04,32.34,83.79,'sensor-zone7-14','2026-06-24 06:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (10,65.615,69.199,0.078,1.145,0.061,27.68,79.81,'sensor-zone10-15','2026-06-26 05:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (5,71.122,36.825,0.075,1.565,0.037,25.29,79.78,'sensor-zone5-16','2026-06-25 05:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (8,20.909,97.762,0.058,2.213,0.059,27.76,50.36,'sensor-zone8-17','2026-06-25 03:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (2,23.102,54.707,0.088,0.677,0.043,32.12,65.69,'sensor-zone2-18','2026-06-26 23:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (2,27.068,54.484,0.044,1.694,0.036,30.53,62.03,'sensor-zone2-19','2026-06-21 07:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (6,31.386,59.912,0.053,0.653,0.06,28.16,76.0,'sensor-zone6-20','2026-06-25 04:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (4,21.229,63.262,0.111,2.444,0.047,27.83,53.52,'sensor-zone4-21','2026-06-25 02:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (5,22.061,29.739,0.012,1.382,0.029,34.74,69.37,'sensor-zone5-22','2026-06-25 19:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (8,12.694,11.349,0.05,1.249,0.034,34.65,58.84,'sensor-zone8-23','2026-06-22 01:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (2,66.659,54.078,0.02,1.611,0.014,26.49,69.7,'sensor-zone2-24','2026-06-23 22:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (2,79.544,23.03,0.117,1.555,0.065,27.26,68.29,'sensor-zone2-25','2026-06-22 08:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (8,27.301,74.738,0.07,1.465,0.014,34.6,53.47,'sensor-zone8-26','2026-06-24 22:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (4,24.848,18.931,0.044,1.425,0.021,29.09,74.13,'sensor-zone4-27','2026-06-22 03:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (5,7.448,41.692,0.05,2.163,0.015,27.33,59.26,'sensor-zone5-28','2026-06-20 11:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (10,54.589,112.704,0.07,1.407,0.055,34.08,78.93,'sensor-zone10-29','2026-06-26 09:27:42');
INSERT INTO env_sensor_readings (zone_id,pm25,pm10,no2,co,o3,temperature,humidity,sensor_id,recorded_at) VALUES (1,17.444,43.837,0.115,1.466,0.03,26.24,74.1,'sensor-zone1-30','2026-06-22 20:27:42');

-- ============================================================
-- SEAT_BOOKINGS (80 rows)
-- ============================================================
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,9,'E4','2026-07-15','08:00','11:00','pending','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (12,10,'C1','2026-06-22','10:00','13:00','approved','2026-06-27 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,10,'A2','2026-07-17','15:00','18:00','approved','2026-06-19 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,10,'D6','2026-07-18','08:00','10:00','pending','2026-06-17 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (12,11,'A2','2026-07-08','13:00','16:00','approved','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,1,'D1','2026-07-07','11:00','13:00','pending','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,14,'A6','2026-07-21','14:00','16:00','pending','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (17,11,'D1','2026-06-30','11:00','14:00','pending','2026-06-18 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (16,15,'D1','2026-06-18','17:00','20:00','approved','2026-06-22 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (11,9,'A5','2026-07-13','08:00','09:00','cancelled','2026-06-24 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (16,2,'B4','2026-07-05','15:00','18:00','pending','2026-06-12 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (17,4,'D5','2026-06-26','13:00','14:00','cancelled','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (4,5,'D3','2026-07-19','11:00','12:00','pending','2026-06-18 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,10,'D2','2026-07-15','15:00','17:00','pending','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (16,15,'C2','2026-07-02','16:00','18:00','pending','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (3,6,'D6','2026-07-11','13:00','16:00','approved','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (17,1,'B5','2026-07-24','12:00','13:00','cancelled','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (5,9,'D1','2026-06-26','13:00','16:00','cancelled','2026-06-25 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (17,13,'C3','2026-07-26','13:00','16:00','pending','2026-06-17 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,7,'C6','2026-07-18','15:00','16:00','approved','2026-06-20 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (11,4,'A4','2026-06-23','17:00','20:00','cancelled','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,12,'C1','2026-06-19','12:00','13:00','pending','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (15,3,'B5','2026-07-13','16:00','19:00','approved','2026-06-22 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,2,'E4','2026-07-26','17:00','18:00','approved','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (9,8,'C4','2026-07-03','17:00','18:00','cancelled','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (11,11,'E2','2026-06-21','14:00','16:00','cancelled','2026-06-18 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (15,12,'C4','2026-07-06','10:00','12:00','cancelled','2026-06-24 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (9,7,'E3','2026-07-23','11:00','14:00','pending','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,1,'E6','2026-06-20','16:00','19:00','approved','2026-06-18 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (9,10,'C2','2026-07-27','10:00','13:00','pending','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (5,15,'A3','2026-07-15','07:00','10:00','pending','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (4,15,'C3','2026-07-13','09:00','10:00','pending','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,9,'C2','2026-07-03','14:00','16:00','approved','2026-06-17 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (5,8,'A2','2026-07-01','17:00','20:00','approved','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,6,'A4','2026-06-17','11:00','14:00','pending','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,10,'D6','2026-07-10','08:00','11:00','pending','2026-06-27 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,6,'E2','2026-06-21','17:00','19:00','cancelled','2026-06-18 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (15,2,'B1','2026-06-19','11:00','13:00','pending','2026-06-20 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,3,'D4','2026-07-10','17:00','20:00','approved','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,12,'B4','2026-06-23','14:00','17:00','approved','2026-06-19 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (3,12,'C2','2026-07-15','14:00','15:00','cancelled','2026-06-24 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (13,9,'C1','2026-07-12','11:00','12:00','cancelled','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (4,11,'B6','2026-07-27','16:00','17:00','pending','2026-06-17 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (9,3,'E2','2026-06-21','15:00','16:00','approved','2026-06-20 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (12,13,'B5','2026-06-17','11:00','12:00','cancelled','2026-06-19 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,2,'A2','2026-06-17','12:00','13:00','approved','2026-06-27 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,5,'A2','2026-07-13','15:00','16:00','approved','2026-06-12 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (16,13,'C5','2026-07-24','08:00','10:00','approved','2026-06-26 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,5,'D6','2026-06-18','07:00','09:00','approved','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (5,8,'D1','2026-06-22','12:00','15:00','pending','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,10,'E5','2026-07-07','13:00','16:00','approved','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,10,'D1','2026-06-24','17:00','20:00','cancelled','2026-06-21 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (15,8,'B4','2026-07-08','14:00','16:00','approved','2026-06-24 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (12,7,'C6','2026-07-03','12:00','13:00','approved','2026-06-12 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (4,2,'A1','2026-07-14','08:00','11:00','approved','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (19,1,'E5','2026-07-22','12:00','15:00','pending','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (15,14,'A3','2026-07-25','11:00','13:00','pending','2026-06-21 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (6,11,'D2','2026-06-23','12:00','15:00','pending','2026-06-19 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,4,'D5','2026-07-26','16:00','19:00','approved','2026-06-27 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (10,1,'B3','2026-07-06','12:00','14:00','pending','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,11,'D1','2026-06-26','17:00','18:00','pending','2026-06-21 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (14,7,'D3','2026-06-27','12:00','14:00','approved','2026-06-25 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (3,3,'B5','2026-06-20','17:00','18:00','pending','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (17,10,'D4','2026-07-04','10:00','13:00','pending','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (5,5,'E4','2026-07-20','17:00','19:00','pending','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (3,1,'B3','2026-06-30','09:00','11:00','pending','2026-06-24 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (2,8,'D2','2026-06-25','13:00','16:00','approved','2026-06-16 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (4,7,'A4','2026-06-18','14:00','15:00','cancelled','2026-06-14 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,7,'D3','2026-06-24','13:00','14:00','cancelled','2026-06-22 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (16,14,'C1','2026-07-14','08:00','09:00','approved','2026-06-15 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,2,'D3','2026-07-08','10:00','12:00','approved','2026-06-25 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,11,'A5','2026-07-19','10:00','12:00','pending','2026-06-23 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (9,2,'B3','2026-06-29','09:00','12:00','pending','2026-06-25 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (7,13,'D4','2026-07-23','16:00','18:00','approved','2026-06-17 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (12,3,'D1','2026-07-17','14:00','17:00','pending','2026-06-19 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,1,'C5','2026-06-21','11:00','13:00','approved','2026-06-26 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (13,14,'C1','2026-06-22','16:00','19:00','approved','2026-06-13 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (20,9,'A4','2026-07-23','17:00','18:00','pending','2026-06-12 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (18,3,'A4','2026-06-23','12:00','15:00','pending','2026-06-22 03:27:42');
INSERT INTO seat_bookings (user_id,room_id,seat_number,booking_date,start_time,end_time,status,created_at) VALUES (3,4,'D4','2026-07-20','15:00','18:00','pending','2026-06-16 03:27:42');

-- ============================================================
-- ROOM_BOOKINGS (25 rows)
-- ============================================================
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (7,11,'2026-07-05 15:27:42','2026-07-05 16:27:42','rejected','2026-06-26 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (2,12,'2026-07-09 20:27:42','2026-07-10 00:27:42','rejected','2026-06-23 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (6,6,'2026-06-24 19:27:42','2026-06-24 21:27:42','rejected','2026-06-23 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (3,14,'2026-07-11 11:27:42','2026-07-11 14:27:42','cancelled','2026-06-17 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (14,12,'2026-06-26 20:27:42','2026-06-26 21:27:42','cancelled','2026-06-19 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (1,13,'2026-07-03 14:27:42','2026-07-03 16:27:42','approved','2026-06-22 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (4,17,'2026-06-29 12:27:42','2026-06-29 14:27:42','pending','2026-06-23 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (9,5,'2026-07-16 18:27:42','2026-07-16 19:27:42','rejected','2026-06-18 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (10,6,'2026-07-04 12:27:42','2026-07-04 14:27:42','approved','2026-06-18 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (12,7,'2026-07-06 10:27:42','2026-07-06 14:27:42','rejected','2026-06-17 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (8,9,'2026-07-11 14:27:42','2026-07-11 18:27:42','approved','2026-06-19 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (5,9,'2026-07-17 17:27:42','2026-07-17 19:27:42','rejected','2026-06-17 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (8,20,'2026-07-06 14:27:42','2026-07-06 18:27:42','cancelled','2026-06-25 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (13,8,'2026-07-11 12:27:42','2026-07-11 15:27:42','pending','2026-06-17 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (14,17,'2026-07-03 18:27:42','2026-07-03 19:27:42','pending','2026-06-23 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (13,4,'2026-06-27 14:27:42','2026-06-27 18:27:42','approved','2026-06-21 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (15,4,'2026-06-29 17:27:42','2026-06-29 20:27:42','pending','2026-06-21 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (7,3,'2026-07-08 15:27:42','2026-07-08 17:27:42','cancelled','2026-06-26 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (4,13,'2026-06-22 15:27:42','2026-06-22 16:27:42','rejected','2026-06-25 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (1,6,'2026-07-01 17:27:42','2026-07-01 19:27:42','cancelled','2026-06-20 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (15,2,'2026-06-24 10:27:42','2026-06-24 13:27:42','approved','2026-06-25 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (12,19,'2026-07-11 18:27:42','2026-07-11 22:27:42','pending','2026-06-23 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (5,9,'2026-06-25 10:27:42','2026-06-25 12:27:42','cancelled','2026-06-17 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (2,16,'2026-06-25 17:27:42','2026-06-25 18:27:42','approved','2026-06-25 03:27:42');
INSERT INTO room_bookings (room_id,user_id,start_time,end_time,status,created_at) VALUES (7,11,'2026-06-22 19:27:42','2026-06-22 22:27:42','approved','2026-06-18 03:27:42');

-- ============================================================
-- ENV_DEVICE_COMMANDS (20 rows)
-- ============================================================
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (7,'led','{"command":"led","value":0}','pending','2026-06-26 04:27:42',NULL);
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (2,'relay','{"command":"relay","value":1}','failed','2026-06-27 01:27:42','2026-06-27 01:27:49');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (6,'fan','{"command":"fan","value":0}','sent','2026-06-26 23:27:42','2026-06-26 23:27:48');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (4,'relay','{"command":"relay","value":1}','sent','2026-06-27 01:27:42','2026-06-27 01:27:48');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (9,'fan','{"command":"fan","value":0}','failed','2026-06-25 07:27:42','2026-06-25 07:27:50');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (11,'led','{"command":"led","value":0}','sent','2026-06-25 06:27:42','2026-06-25 06:27:50');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (1,'fan','{"command":"fan","value":0}','sent','2026-06-26 15:27:42','2026-06-26 15:27:43');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (6,'fan','{"command":"fan","value":1}','pending','2026-06-25 21:27:42',NULL);
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (5,'relay','{"command":"relay","value":0}','sent','2026-06-26 05:27:42','2026-06-26 05:27:43');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (14,'fan','{"command":"fan","value":1}','sent','2026-06-26 04:27:42','2026-06-26 04:27:49');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (15,'ac','{"command":"ac","value":1}','failed','2026-06-26 06:27:42','2026-06-26 06:27:45');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (8,'ac','{"command":"ac","value":1}','pending','2026-06-26 10:27:42',NULL);
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (13,'relay','{"command":"relay","value":1}','sent','2026-06-26 00:27:42','2026-06-26 00:27:52');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (14,'led','{"command":"led","value":1}','sent','2026-06-26 21:27:42','2026-06-26 21:27:44');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (6,'fan','{"command":"fan","value":1}','failed','2026-06-25 13:27:42','2026-06-25 13:27:49');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (3,'ac','{"command":"ac","value":1}','failed','2026-06-27 01:27:42','2026-06-27 01:27:48');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (10,'ac','{"command":"ac","value":1}','sent','2026-06-26 23:27:42','2026-06-26 23:27:49');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (6,'led','{"command":"led","value":0}','sent','2026-06-25 13:27:42','2026-06-25 13:27:50');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (1,'led','{"command":"led","value":0}','sent','2026-06-25 10:27:42','2026-06-25 10:27:48');
INSERT INTO env_device_commands (room_id,command_type,payload,status,created_at,executed_at) VALUES (6,'ac','{"command":"ac","value":0}','pending','2026-06-26 18:27:42',NULL);