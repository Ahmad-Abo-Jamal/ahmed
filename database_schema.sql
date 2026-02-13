-- ===================================
-- Enhanced Admin Dashboard Schema
-- Plain Text Password Version
-- ===================================

-- Drop existing tables
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS admin_users;

-- ===================================
-- Admin Users Table
-- ===================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- Plain text password
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (CHANGE PASSWORD AFTER FIRST LOGIN)
INSERT INTO admin_users (username, email, password, full_name, role, status) 
VALUES ('admin', 'admin@example.com', 'Admin@123456', 'المسؤول الرئيسي', 'admin', 'active');

-- ===================================
-- Contact Messages Table
-- ===================================
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    admin_reply TEXT NULL,
    replied_by INT NULL,
    replied_at DATETIME NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at),
    INDEX idx_email (email),
    FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Articles Table
-- ===================================
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content TEXT NOT NULL,
    category ENUM('book', 'course', 'service', 'news', 'article') DEFAULT 'article',
    badge VARCHAR(50) NULL,
    image_url VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    publish_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_featured (featured),
    INDEX idx_publish_date (publish_date),
    FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Services Table
-- ===================================
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(100) DEFAULT 'fa-star',
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    price_min DECIMAL(10,2) NULL,
    price_max DECIMAL(10,2) NULL,
    duration VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Site Settings Table
-- ===================================
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'email', 'url') DEFAULT 'text',
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, setting_group, description, is_public) VALUES
('site_name', 'موقعي الإلكتروني', 'text', 'general', 'اسم الموقع', TRUE),
('site_email', 'info@example.com', 'email', 'general', 'البريد الإلكتروني الرئيسي', TRUE),
('site_phone', '+966500000000', 'text', 'general', 'رقم الهاتف', TRUE),
('site_description', 'وصف الموقع الإلكتروني', 'text', 'general', 'وصف الموقع', TRUE),
('whatsapp_number', '+966500000000', 'text', 'contact', 'رقم الواتساب', TRUE),
('facebook_url', '', 'url', 'social', 'رابط فيسبوك', TRUE),
('instagram_url', '', 'url', 'social', 'رابط انستقرام', TRUE),
('linkedin_url', '', 'url', 'social', 'رابط لينكد إن', TRUE),
('youtube_url', '', 'url', 'social', 'رابط يوتيوب', TRUE),
('twitter_url', '', 'url', 'social', 'رابط تويتر', TRUE),
('smtp_host', 'smtp.example.com', 'text', 'email', 'SMTP Host', FALSE),
('smtp_port', '587', 'number', 'email', 'SMTP Port', FALSE),
('smtp_username', '', 'text', 'email', 'SMTP Username', FALSE),
('smtp_password', '', 'text', 'email', 'SMTP Password', FALSE),
('newsletter_enabled', '1', 'boolean', 'features', 'تفعيل النشرة البريدية', FALSE),
('maintenance_mode', '0', 'boolean', 'features', 'وضع الصيانة', FALSE);

-- ===================================
-- Activity Log Table
-- ===================================
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at),
    INDEX idx_table (table_name),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Views for quick access
-- ===================================
CREATE VIEW vw_unread_messages AS
SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new';

CREATE VIEW vw_active_articles AS
SELECT COUNT(*) as count FROM articles WHERE status = 'published';

CREATE VIEW vw_active_services AS
SELECT COUNT(*) as count FROM services WHERE status = 'active';

CREATE VIEW vw_recent_activity AS
SELECT a.*, u.username, u.full_name 
FROM activity_log a
JOIN admin_users u ON a.admin_id = u.id
ORDER BY a.created_at DESC
LIMIT 50;
