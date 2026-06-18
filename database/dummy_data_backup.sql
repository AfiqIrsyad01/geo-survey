-- Dummy Data for GeoSurvey System
-- Import this after importing schema.sql

USE geoservedb;

-- 1. Insert Users (Password is 'password' for all)
-- Passwords are Hashed using Laravel default (Bcrypt)
-- password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES
('Admin User', 'admin@geosurvey.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW()),
('HOD User', 'hod@geosurvey.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hod', NOW(), NOW()),
('Staff User', 'staff@geosurvey.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', NOW(), NOW());

-- 2. Insert Projects
INSERT INTO projects (name, description, boundary, created_at, updated_at) VALUES
('Kuala Lumpur Central Zone', 'Main urban survey area for infrastructure audit.', ST_GeomFromText('POLYGON((101.6800 3.1350, 101.6900 3.1350, 101.6900 3.1450, 101.6800 3.1450, 101.6800 3.1350))'), NOW(), NOW()),
('Petaling Jaya Satellite Office', 'Secondary zone for residential survey.', ST_GeomFromText('POLYGON((101.6000 3.1000, 101.6100 3.1000, 101.6100 3.1100, 101.6000 3.1100, 101.6000 3.1000))'), NOW(), NOW());

-- 3. Insert Sample Surveys
INSERT INTO surveys (project_id, user_id, status, survey_date, created_at, updated_at) VALUES
(1, 3, 'pending', '2026-03-15', NOW(), NOW()),
(1, 3, 'approved', '2026-03-14', NOW(), NOW()),
(2, 3, 'pending', '2026-03-16', NOW(), NOW());

-- 4. Insert Survey Details (GPS Points)
INSERT INTO survey_details (survey_id, location, attributes, created_at, updated_at) VALUES
(1, ST_GeomFromText('POINT(101.6850 3.1400)'), '{"condition": "Good", "type": "Manhole"}', NOW(), NOW()),
(2, ST_GeomFromText('POINT(101.6860 3.1410)'), '{"condition": "Needs Repair", "type": "Pole"}', NOW(), NOW()),
(3, ST_GeomFromText('POINT(101.6050 3.1050)'), '{"condition": "Excelent", "type": "Drainage"}', NOW(), NOW());

-- 5. Insert Notifications
INSERT INTO app_notifications (user_id, message, is_read, created_at, updated_at) VALUES
(1, 'New survey submitted by Staff User', 0, NOW(), NOW()),
(2, 'Survey #2 needs your approval', 0, NOW(), NOW());
