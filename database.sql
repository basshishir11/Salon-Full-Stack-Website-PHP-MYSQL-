-- Database: sharma_salon

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin (Password: salon@2026)
-- We will insert this hash later or user can run a script. For now just structure.
-- Hash for 'salon@2026' is $2y$10$w... (Example) 

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- Men, Women, Unisex
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duration_mins INT NOT NULL DEFAULT 20,
    gender_type ENUM('Men', 'Women', 'Unisex') NOT NULL,
    image_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL, -- Indexed for search
    referral_code VARCHAR(20) UNIQUE,
    referred_by_customer_id INT DEFAULT NULL,
    visit_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (phone)
);

CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) NOT NULL, -- B-1, G-1
    gender ENUM('Men', 'Women') NOT NULL,
    customer_id INT NOT NULL,
    services_summary TEXT,
    status ENUM('Waiting', 'In Service', 'Completed', 'Cancelled') DEFAULT 'Waiting',
    estimated_wait_mins INT DEFAULT 0,
    estimated_serve_time DATETIME DEFAULT NULL,
    notify_15min_sent TINYINT(1) DEFAULT 0,
    notify_turn_sent TINYINT(1) DEFAULT 0,
    appointment_date DATE DEFAULT NULL,
    appointment_time TIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX (status),
    INDEX (created_at),
    INDEX (appointment_date, appointment_time)
);

CREATE TABLE IF NOT EXISTS token_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    boys_counter INT DEFAULT 0,
    girls_counter INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    final_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES tokens(id)
);

CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    value_amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('Pending', 'Claimed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS loyalty_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visits_required INT NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    value_amount DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customer_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    milestone_id INT NOT NULL,
    status ENUM('Earned', 'Claimed') DEFAULT 'Earned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (milestone_id) REFERENCES loyalty_milestones(id)
);

CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_customer_id INT NOT NULL,
    referred_customer_id INT NOT NULL,
    booking_id INT NOT NULL, -- Use token_id logic if needed, or generic booking ref
    status ENUM('Pending', 'Successful') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_customer_id) REFERENCES customers(id),
    FOREIGN KEY (referred_customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_customer_id INT NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    status ENUM('Available', 'Claimed') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (referrer_customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT
);

CREATE TABLE IF NOT EXISTS notification_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    subscription_json TEXT NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    notification_15min TINYINT(1) DEFAULT 0,
    notification_turn TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Config
INSERT IGNORE INTO settings (`key`, `value`) VALUES 
('shop_name', 'Sharma Salon & Spa'),
('shop_address', 'Thamel, Kathmandu, Nepal'),
('shop_email', 'saloon@11gmail.com'),
('phone_call', '+977-9800000000'),
('phone_whatsapp', '9779800000000'),
('whatsapp_message', 'Hello, I want to book a token.'),
('avg_service_duration', '20'),
('notify_before_mins', '15'),
('enable_browser_push', '1'),
('enable_sms', '0'),
('enable_whatsapp_notify', '0'),
('maps_embed_code', '');

-- Insert Default Categories
INSERT IGNORE INTO categories (name) VALUES ('Men'), ('Women'), ('Unisex');

-- Insert Default Admin (Password: salon@2026 -> $2y$10$Something)
-- We will handle the seeding in a separate script or manual instruction if needed, 
-- but best to have at least one admin to login.
-- Generating a BCrypt hash for 'salon@2026' for testing purposes.
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi is a placeholder password hash (password)
-- Let's use a known hash for 'salon@2026' in the insertion below or handle in setup.
-- For now we insert 'admin' user.
