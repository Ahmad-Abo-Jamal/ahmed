-- Advanced Features Database Schema
-- This migration adds 5 new major feature modules

-- 1. EMAIL NOTIFICATIONS SYSTEM
CREATE TABLE IF NOT EXISTS email_templates (
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
);

CREATE TABLE IF NOT EXISTS notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    notification_type VARCHAR(50) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_queue (
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
);

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_queue_id INT NOT NULL,
    event_type VARCHAR(50),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_queue_id) REFERENCES notification_queue(id) ON DELETE CASCADE
);

-- 2. ADVANCED ANALYTICS SYSTEM
CREATE TABLE IF NOT EXISTS analytics_page_views (
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
);

CREATE TABLE IF NOT EXISTS analytics_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    event_category VARCHAR(50),
    user_id INT,
    session_id VARCHAR(100),
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_name_date (event_name, created_at),
    INDEX idx_user_date (user_id, created_at)
);

CREATE TABLE IF NOT EXISTS analytics_sessions (
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
);

CREATE TABLE IF NOT EXISTS analytics_daily_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL UNIQUE,
    total_visits INT DEFAULT 0,
    unique_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_page_views INT DEFAULT 0,
    avg_session_duration INT DEFAULT 0,
    bounce_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. E-COMMERCE SYSTEM
CREATE TABLE IF NOT EXISTS products (
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
);

CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(500),
    alt_text_ar VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS shopping_cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
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
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name_ar VARCHAR(255),
    quantity INT NOT NULL,
    unit_price DECIMAL(12, 2),
    subtotal DECIMAL(12, 2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 4. REVIEWS AND RATINGS SYSTEM
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    reviewable_type ENUM('product', 'article', 'service', 'influencer') NOT NULL,
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
);

CREATE TABLE IF NOT EXISTS review_helpfulness (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    user_id INT,
    is_helpful BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review_user (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS review_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

-- 5. ADVANCED TAGS AND CATEGORIES SYSTEM
CREATE TABLE IF NOT EXISTS tag_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(100),
    description_ar TEXT,
    color_code VARCHAR(7),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tags (
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
);

CREATE TABLE IF NOT EXISTS taggable_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_id INT NOT NULL,
    taggable_type ENUM('article', 'product', 'service', 'page') NOT NULL,
    taggable_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_item (tag_id, taggable_type, taggable_id),
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_taggable (taggable_type, taggable_id)
);

CREATE TABLE IF NOT EXISTS category_hierarchy (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT,
    child_id INT NOT NULL,
    level INT DEFAULT 1,
    display_order INT DEFAULT 0,
    UNIQUE KEY unique_hierarchy (parent_id, child_id),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Add new columns to existing tables if they don't exist
ALTER TABLE articles ADD COLUMN IF NOT EXISTS rating_average DECIMAL(3, 2) DEFAULT 0;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS rating_count INT DEFAULT 0;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;

ALTER TABLE services ADD COLUMN IF NOT EXISTS rating_average DECIMAL(3, 2) DEFAULT 0;
ALTER TABLE services ADD COLUMN IF NOT EXISTS rating_count INT DEFAULT 0;

ALTER TABLE categories ADD COLUMN IF NOT EXISTS meta_description TEXT;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS meta_keywords VARCHAR(500);
ALTER TABLE categories ADD COLUMN IF NOT EXISTS seo_friendly_url VARCHAR(255);

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

-- Indexes for performance
CREATE INDEX idx_products_active ON products(is_active, created_at);
CREATE INDEX idx_orders_status ON orders(status, created_at);
CREATE INDEX idx_reviews_rating ON reviews(rating, status);
