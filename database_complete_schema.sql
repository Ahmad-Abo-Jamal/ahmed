-- ===================================
-- COMPLETE DATABASE SCHEMA
-- Consolidated all features in one file
-- ===================================

-- Drop existing tables (in correct dependency order)
DROP TABLE IF EXISTS category_hierarchy;
DROP TABLE IF EXISTS taggable_items;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS tag_groups;
DROP TABLE IF EXISTS review_helpfulness;
DROP TABLE IF EXISTS review_images;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS shopping_cart;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS influencer_portfolio;
DROP TABLE IF EXISTS influencer_contacts;
DROP TABLE IF EXISTS influencers;
DROP TABLE IF EXISTS newsletter_campaigns;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS dictionary;
DROP TABLE IF EXISTS message_conversations;
DROP TABLE IF EXISTS notification_logs;
DROP TABLE IF EXISTS notification_queue;
DROP TABLE IF EXISTS notification_settings;
DROP TABLE IF EXISTS email_templates;
DROP TABLE IF EXISTS analytics_daily_stats;
DROP TABLE IF EXISTS analytics_sessions;
DROP TABLE IF EXISTS analytics_events;
DROP TABLE IF EXISTS analytics_page_views;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS sectors;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin_users;

-- ===================================
-- 1. ADMIN USERS TABLE
-- ===================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    can_manage_users BOOLEAN DEFAULT FALSE,
    can_manage_newsletter BOOLEAN DEFAULT FALSE,
    can_manage_influencers BOOLEAN DEFAULT FALSE,
    can_manage_dictionary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account
INSERT INTO admin_users (username, email, password, full_name, role, status) 
VALUES ('admin', 'admin@example.com', 'Admin@123456', 'المسؤول الرئيسي', 'admin', 'active');

-- ===================================
-- 2. USERS TABLE
-- ===================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    avatar_url VARCHAR(500),
    country VARCHAR(100),
    preferred_language ENUM('ar', 'en') DEFAULT 'ar',
    email_verified BOOLEAN DEFAULT FALSE,
    newsletter_subscribed BOOLEAN DEFAULT FALSE,
    last_login DATETIME NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 3. SITE SETTINGS TABLE
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
-- 4. SECTORS TABLE
-- ===================================
CREATE TABLE sectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    icon VARCHAR(100) DEFAULT 'fa-briefcase',
    description TEXT,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 5. BRANDS TABLE
-- ===================================
CREATE TABLE brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sector_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    category VARCHAR(255),
    category_ar VARCHAR(255),
    description TEXT,
    description_ar TEXT,
    icon VARCHAR(100) DEFAULT 'fa-star',
    logo_url VARCHAR(500),
    logo_color VARCHAR(7) DEFAULT '#08137b',
    logo_color_secondary VARCHAR(7) DEFAULT '#4f09a7',
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sector (sector_id),
    INDEX idx_status (status),
    INDEX idx_order (display_order),
    FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 6. ARTICLES TABLE
-- ===================================
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    category ENUM('book', 'course', 'service', 'news', 'article') DEFAULT 'article',
    badge VARCHAR(50) NULL,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views INT DEFAULT 0,
    view_count INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    publish_date DATETIME NULL,
    word_count INT DEFAULT 0,
    reading_time INT DEFAULT 0,
    rating_average DECIMAL(3, 2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_featured (featured),
    INDEX idx_publish_date (publish_date),
    INDEX idx_author (author_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 7. SERVICES TABLE
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
    rating_average DECIMAL(3, 2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 8. CONTACT MESSAGES TABLE
-- ===================================
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    service VARCHAR(100),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    admin_reply LONGTEXT NULL,
    admin_notes TEXT,
    replied_by INT NULL,
    replied_at DATETIME NULL,
    read_at DATETIME NULL,
    thread_id INT DEFAULT NULL,
    parent_message_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at),
    INDEX idx_email (email),
    INDEX idx_thread (thread_id),
    FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 9. MESSAGE CONVERSATIONS TABLE
-- ===================================
CREATE TABLE message_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT UNIQUE NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open', 'waiting', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    last_message_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_thread (thread_id),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_created (created_at),
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 10. ACTIVITY LOG TABLE
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
-- 11. DICTIONARY TABLE
-- ===================================
CREATE TABLE dictionary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    word_ar VARCHAR(255) UNIQUE NOT NULL,
    pronunciation VARCHAR(255),
    definition_ar LONGTEXT NOT NULL,
    examples LONGTEXT,
    synonyms VARCHAR(500),
    antonyms VARCHAR(500),
    word_type ENUM('noun', 'verb', 'adjective', 'adverb', 'preposition', 'other') DEFAULT 'noun',
    category VARCHAR(100),
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    usage_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_word (word_ar),
    INDEX idx_category (category),
    INDEX idx_featured (is_featured),
    INDEX idx_level (difficulty_level),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample dictionary entries
INSERT IGNORE INTO dictionary (word_ar, pronunciation, definition_ar, examples, synonyms, category, difficulty_level, is_featured) VALUES
('الريادة', 'ar-riyādah', 'القدرة على إنشاء مشروع جديد وإدارته بكفاءة والاستعداد لتحمل المخاطر', 'أحمد يسعى لنشر ثقافة الريادة بين الشباب', 'الإدارة، المبادرة', 'أعمال', 'intermediate', TRUE),
('التسويق', 'at-taswīq', 'عملية تروج السلع والخدمات وبيعها للعملاء', 'التسويق الرقمي أصبح ضرورياً في عصرنا الحالي', 'الترويج، البيع', 'أعمال', 'beginner', TRUE),
('الابتكار', 'al-ibtikār', 'إيجاد فكرة جديدة أو طريقة جديدة لعمل شيء ما', 'الابتكار هو مفتاح النجاح في الأعمال', 'الإبداع، التجديد', 'عام', 'intermediate', TRUE);

-- ===================================
-- 12. NEWSLETTER SUBSCRIBERS TABLE
-- ===================================
CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    country VARCHAR(100),
    subscribe_reason VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    confirmation_token VARCHAR(255),
    is_confirmed BOOLEAN DEFAULT FALSE,
    last_received_at DATETIME NULL,
    unsubscribe_reason VARCHAR(255),
    unsubscribed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_confirmed (is_confirmed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 13. NEWSLETTER CAMPAIGNS TABLE
-- ===================================
CREATE TABLE newsletter_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    recipients_count INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    open_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'sent', 'archived') DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 14. INFLUENCERS TABLE
-- ===================================
CREATE TABLE influencers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255),
    slug VARCHAR(255) UNIQUE NOT NULL,
    bio TEXT,
    bio_ar TEXT,
    image_url VARCHAR(500),
    category VARCHAR(100),
    category_ar VARCHAR(100),
    specialization VARCHAR(255),
    specialization_ar VARCHAR(255),
    follower_count INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0,
    platform VARCHAR(100),
    platform_url VARCHAR(500),
    email VARCHAR(100),
    phone VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    rate_per_post DECIMAL(10,2) NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    verification_status ENUM('unverified', 'verified', 'premium') DEFAULT 'unverified',
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    contacts_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_platform (platform),
    INDEX idx_featured (is_featured),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample influencers
INSERT IGNORE INTO influencers (name, name_ar, slug, bio, bio_ar, follower_count, platform, category, category_ar, specialization, specialization_ar, is_featured, verification_status, status) VALUES
('Mohammad Ali', 'محمد علي', 'mohammad-ali', 'Digital Marketing Expert from Saudi Arabia', 'خبير التسويق الرقمي من السعودية', 150000, 'instagram', 'marketing', 'تسويق', 'Social Media Marketing', 'تسويق وسائل التواصل', TRUE, 'verified', 'active'),
('Fatima Ahmed', 'فاطمة أحمد', 'fatima-ahmed', 'Fashion Blogger & Content Creator', 'مدونة الموضة ومنشئة محتوى', 250000, 'instagram', 'fashion', 'الموضة', 'Fashion & Lifestyle', 'الموضة ونمط الحياة', TRUE, 'verified', 'active'),
('Ali Hassan', 'علي حسن', 'ali-hassan', 'Tech Reviewer & Educational Content Creator', 'خبير تقييم التقنية ومنتج محتوى تعليمي', 180000, 'youtube', 'technology', 'التقنية', 'Tech Reviews & Education', 'مراجعات التقنية والتعليم', FALSE, 'verified', 'active');

-- ===================================
-- 15. INFLUENCER CONTACTS TABLE
-- ===================================
CREATE TABLE influencer_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    influencer_id INT NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    budget DECIMAL(10,2) NULL,
    proposal_type VARCHAR(100),
    response_status ENUM('pending', 'interested', 'negotiating', 'agreed', 'rejected', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    replied_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_influencer (influencer_id),
    INDEX idx_status (response_status),
    INDEX idx_created (created_at),
    FOREIGN KEY (influencer_id) REFERENCES influencers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 16. INFLUENCER PORTFOLIO TABLE
-- ===================================
CREATE TABLE influencer_portfolio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    influencer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    link VARCHAR(500),
    engagement_metrics TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_influencer (influencer_id),
    FOREIGN KEY (influencer_id) REFERENCES influencers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 17. EMAIL TEMPLATES TABLE
-- ===================================
CREATE TABLE email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    name_ar VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    subject_ar VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample email templates
INSERT IGNORE INTO email_templates (name, name_ar, subject, subject_ar, content, variables) VALUES
('welcome_email', 'رسالة الترحيب', 'Welcome to Our Platform', 'أهلا وسهلا بك', 
 '<h2>أهلا وسهلا {{username}}</h2><p>شكراً لتسجيلك معنا</p>',
 '["username", "email"]'),
('newsletter_confirmation', 'تأكيد الاشتراك في النشرة البريدية', 'Confirm Your Newsletter Subscription', 
 'تأكيد اشتراكك في النشرة البريدية', 
 '<p>أنت مشترك الآن في نشرتنا البريدية</p>',
 '["username"]'),
('order_confirmation', 'تأكيد الطلب', 'Order Confirmation', 'تأكيد طلبك',
 '<h2>شكراً لطلبك #{{order_number}}</h2><p>سنرسل لك تحديثات عن الشحن قريباً</p>',
 '["order_number", "customer_name"]');

-- ===================================
-- 18. NOTIFICATION SETTINGS TABLE
-- ===================================
CREATE TABLE notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    notification_type VARCHAR(50) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 19. NOTIFICATION QUEUE TABLE
-- ===================================
CREATE TABLE notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    email_template_id INT NOT NULL,
    recipient_email VARCHAR(255),
    variables JSON,
    status ENUM('pending', 'sent', 'failed', 'bounce') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    last_retry TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_template_id) REFERENCES email_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 20. NOTIFICATION LOGS TABLE
-- ===================================
CREATE TABLE notification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_queue_id INT NOT NULL,
    event_type VARCHAR(50),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_queue_id) REFERENCES notification_queue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 21. ANALYTICS TABLES
-- ===================================
CREATE TABLE analytics_page_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_type VARCHAR(50),
    page_id INT,
    page_title VARCHAR(255),
    user_id INT,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    view_duration INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_type_date (page_type, created_at),
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    event_category VARCHAR(50),
    user_id INT,
    session_id VARCHAR(100),
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_name_date (event_name, created_at),
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(100) NOT NULL UNIQUE,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    country VARCHAR(50),
    city VARCHAR(100),
    device_type VARCHAR(50),
    browser VARCHAR(100),
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP NULL,
    page_views INT DEFAULT 0,
    duration_seconds INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_daily_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL UNIQUE,
    total_visits INT DEFAULT 0,
    unique_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_page_views INT DEFAULT 0,
    avg_session_duration INT DEFAULT 0,
    bounce_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 22. CATEGORIES TABLE
-- ===================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_ar TEXT,
    description_en TEXT,
    parent_id INT,
    icon VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    seo_friendly_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 23. PRODUCTS TABLE
-- ===================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_ar LONGTEXT,
    description_en LONGTEXT,
    image_url VARCHAR(500),
    category_id INT,
    price DECIMAL(12, 2) NOT NULL,
    cost_price DECIMAL(12, 2),
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    FULLTEXT idx_search (name_ar, description_ar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 24. PRODUCT IMAGES TABLE
-- ===================================
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(500),
    alt_text_ar VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 25. SHOPPING CART TABLE
-- ===================================
CREATE TABLE shopping_cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 26. ORDERS TABLE
-- ===================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    shipping_address TEXT,
    billing_address TEXT,
    total_amount DECIMAL(12, 2),
    tax_amount DECIMAL(12, 2) DEFAULT 0,
    shipping_cost DECIMAL(12, 2) DEFAULT 0,
    discount_amount DECIMAL(12, 2) DEFAULT 0,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 27. ORDER ITEMS TABLE
-- ===================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name_ar VARCHAR(255),
    quantity INT NOT NULL,
    unit_price DECIMAL(12, 2),
    subtotal DECIMAL(12, 2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 28. REVIEWS TABLE
-- ===================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    reviewable_type ENUM('product', 'article', 'service', 'influencer', 'dictionary') NOT NULL,
    reviewable_id INT NOT NULL,
    title_ar VARCHAR(255),
    content_ar LONGTEXT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    unhelpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reviewable (reviewable_type, reviewable_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 29. REVIEW HELPFULNESS TABLE
-- ===================================
CREATE TABLE review_helpfulness (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    user_id INT,
    is_helpful BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review_user (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 30. REVIEW IMAGES TABLE
-- ===================================
CREATE TABLE review_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 31. TAG GROUPS TABLE
-- ===================================
CREATE TABLE tag_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(100),
    description_ar TEXT,
    color_code VARCHAR(7),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 32. TAGS TABLE
-- ===================================
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_group_id INT,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    slug VARCHAR(100) NOT NULL UNIQUE,
    description_ar TEXT,
    color_code VARCHAR(7),
    usage_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tag_group_id) REFERENCES tag_groups(id) ON DELETE SET NULL,
    INDEX idx_group (tag_group_id),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 33. TAGGABLE ITEMS TABLE
-- ===================================
CREATE TABLE taggable_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_id INT NOT NULL,
    taggable_type ENUM('article', 'product', 'service', 'page') NOT NULL,
    taggable_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_item (tag_id, taggable_type, taggable_id),
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_taggable (taggable_type, taggable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 34. CATEGORY HIERARCHY TABLE
-- ===================================
CREATE TABLE category_hierarchy (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT,
    child_id INT NOT NULL,
    level INT DEFAULT 1,
    display_order INT DEFAULT 0,
    UNIQUE KEY unique_hierarchy (parent_id, child_id),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- VIEWS FOR QUICK ACCESS
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

CREATE VIEW v_active_subscribers AS
SELECT email, full_name, country, is_confirmed, created_at
FROM newsletter_subscribers
WHERE is_active = TRUE AND is_confirmed = TRUE
ORDER BY created_at DESC;

CREATE VIEW v_influencer_summary AS
SELECT 
    i.id,
    i.name_ar as name,
    i.category_ar as category,
    i.platform,
    i.follower_count,
    i.engagement_rate,
    COUNT(DISTINCT ic.id) as contact_count,
    COUNT(DISTINCT ip.id) as portfolio_count,
    i.status
FROM influencers i
LEFT JOIN influencer_contacts ic ON i.id = ic.influencer_id
LEFT JOIN influencer_portfolio ip ON i.id = ip.influencer_id
GROUP BY i.id
ORDER BY i.is_featured DESC, i.follower_count DESC;

-- ===================================
-- PERFORMANCE INDEXES
-- ===================================
CREATE INDEX idx_products_active ON products(is_active, created_at);
CREATE INDEX idx_orders_status ON orders(status, created_at);
CREATE INDEX idx_reviews_rating ON reviews(rating, status);
CREATE INDEX idx_articles_author ON articles(author_id);
CREATE INDEX idx_articles_created ON articles(created_at);
CREATE INDEX idx_users_created ON users(created_at);

-- ===================================
-- SCHEMA COMPLETE
-- ===================================
