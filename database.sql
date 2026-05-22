-- ============================================================
-- GymPulse Gym Management Information System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS gympulse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gympulse_db;

-- ========================
-- USERS (Authentication)
-- ========================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','trainer','staff') NOT NULL DEFAULT 'staff',
    full_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- NOTIFICATIONS
-- ========================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================
-- TRAINERS
-- ========================
CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    specialization VARCHAR(200),
    status ENUM('Active','On Leave','Inactive') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- MEMBERSHIP PLANS
-- ========================
CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description VARCHAR(255)
);

-- ========================
-- MEMBERS
-- ========================
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(30),
    plan_id INT,
    trainer_id INT,
    status ENUM('Active','Pending','Expired','Inactive') DEFAULT 'Active',
    joined_date DATE DEFAULT (CURRENT_DATE),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
);

-- ========================
-- ENROLLMENTS
-- ========================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_code VARCHAR(20) NOT NULL UNIQUE,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    processed_by INT,
    status ENUM('Active','Pending','Expired') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id),
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================
-- PAYMENTS
-- ========================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(30) NOT NULL UNIQUE,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Cash','GCash','Card','Bank Transfer') DEFAULT 'Cash',
    payment_date DATE DEFAULT (CURRENT_DATE),
    cashier_id INT,
    status ENUM('Paid','Unpaid','Refunded') DEFAULT 'Paid',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================
-- ATTENDANCE
-- ========================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_code VARCHAR(20) NOT NULL UNIQUE,
    member_id INT NOT NULL,
    attendance_date DATE DEFAULT (CURRENT_DATE),
    time_in TIME,
    time_out TIME,
    duration_minutes INT DEFAULT 0,
    status ENUM('Present','Absent') DEFAULT 'Present',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- ========================
-- SEED DATA
-- ========================

-- Default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, full_name) VALUES
('admin@gympulse.com', 'admin@gympulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Miguel Reyes'),
('trainer01', 'trainer01@gympulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'Carlo Ramos'),
('staff001', 'staff001@gympulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Staff Member');

-- Membership Plans
INSERT INTO membership_plans (plan_name, duration_days, price, description) VALUES
('Monthly', 30, 1200.00, '30-day membership'),
('Quarterly', 90, 3200.00, '90-day membership'),
('Annual', 365, 10800.00, '365-day membership'),
('Day Pass', 1, 150.00, 'Single day access');

-- Trainers
INSERT INTO trainers (trainer_code, full_name, specialization, status) VALUES
('TRN-001', 'Carlo Ramos', 'Weightlifting & Strength', 'Active'),
('TRN-002', 'Diana Cruz', 'Yoga & Flexibility', 'Active'),
('TRN-003', 'Marco Reyes', 'HIIT & Cardio', 'Active'),
('TRN-004', 'Sofia Lim', 'Boxing & Martial Arts', 'On Leave');

-- Members
INSERT INTO members (member_code, first_name, last_name, email, phone, plan_id, trainer_id, status, joined_date) VALUES
('GYM-0481', 'Andrea', 'Lopez', 'andrea@email.com', '+63 912 345 6780', 1, 1, 'Active', '2025-04-18'),
('GYM-0480', 'Rico', 'Santos', 'rico@email.com', '+63 912 345 6781', 3, NULL, 'Active', '2025-04-17'),
('GYM-0479', 'Bianca', 'Reyes', 'bianca@email.com', '+63 912 345 6782', 2, 2, 'Pending', '2025-04-16'),
('GYM-0478', 'Mark', 'Torres', 'mark@email.com', '+63 912 345 6783', 1, 1, 'Active', '2025-04-15'),
('GYM-0477', 'Jane', 'Navarro', 'jane@email.com', '+63 912 345 6784', 4, NULL, 'Expired', '2025-04-15'),
('GYM-0476', 'Luis', 'Garcia', 'luis@email.com', '+63 912 345 6785', 2, 3, 'Active', '2025-04-14'),
('GYM-0475', 'Maria', 'Cruz', 'maria@email.com', '+63 912 345 6786', 3, 2, 'Active', '2025-04-10');

-- Enrollments
INSERT INTO enrollments (enrollment_code, member_id, plan_id, start_date, expiry_date, amount, processed_by, status) VALUES
('ENR-0001', 1, 1, '2025-04-01', '2025-04-30', 1200.00, 1, 'Active'),
('ENR-0002', 2, 3, '2025-01-01', '2025-12-31', 10800.00, 3, 'Active'),
('ENR-0003', 3, 2, '2025-04-01', '2025-06-30', 3200.00, 1, 'Pending'),
('ENR-0004', 4, 1, '2025-03-18', '2025-04-17', 1200.00, 1, 'Expired');

-- Payments
INSERT INTO payments (receipt_no, member_id, plan_id, amount, method, payment_date, cashier_id, status) VALUES
('RCP-2025-001', 1, 1, 1200.00, 'GCash', '2025-04-01', 1, 'Paid'),
('RCP-2025-002', 2, 3, 10800.00, 'Bank Transfer', '2025-01-01', 3, 'Paid'),
('RCP-2025-003', 3, 2, 3200.00, 'Cash', '2025-04-01', 1, 'Unpaid'),
('RCP-2025-004', 4, 1, 1200.00, 'Card', '2025-03-18', 1, 'Paid'),
('RCP-2025-005', 6, 2, 3200.00, 'GCash', '2025-04-02', 3, 'Paid');

-- Attendance
INSERT INTO attendance (log_code, member_id, attendance_date, time_in, time_out, duration_minutes, status) VALUES
('ATT-0001', 1, '2025-04-20', '06:30:00', '08:15:00', 105, 'Present'),
('ATT-0002', 2, '2025-04-20', '07:00:00', '09:00:00', 120, 'Present'),
('ATT-0003', 3, '2025-04-20', NULL, NULL, 0, 'Absent'),
('ATT-0004', 4, '2025-04-19', '05:30:00', '07:00:00', 90, 'Present'),
('ATT-0005', 6, '2025-04-19', '08:00:00', '10:00:00', 120, 'Present');

-- Notifications for admin
INSERT INTO notifications (user_id, title, message) VALUES
(1, 'Expiring Memberships', '4 memberships are expiring within 7 days. Review and notify members.'),
(1, 'New Member Registered', 'Andrea M. Lopez has registered under Monthly plan.'),
(1, 'Payment Pending', 'Bianca C. Reyes has an unpaid balance of ₱3,200.'),
(1, 'Trainer Update', 'Sofia Lim is currently on leave. Reassign her members if needed.'),
(1, 'Attendance Alert', 'Bianca C. Reyes was absent today.');
