-- ===================================
-- Add Sectors & Brands Tables
-- ===================================

-- Sectors Table
CREATE TABLE IF NOT EXISTS sectors (
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

-- Brands Table
CREATE TABLE IF NOT EXISTS brands (
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

-- Insert sample data
INSERT INTO sectors (name, name_ar, icon, description, display_order, status) VALUES
('Finance', 'المالية والبنوك', 'fa-university', 'Banking and Financial Services', 1, 'active'),
('Technology', 'التكنولوجيا', 'fa-laptop', 'Technology and Software Solutions', 2, 'active'),
('Retail', 'التجزئة', 'fa-shopping-bag', 'Retail and E-commerce', 3, 'active'),
('Healthcare', 'الصحة', 'fa-hospital', 'Healthcare and Medical Services', 4, 'active'),
('Energy', 'الطاقة', 'fa-bolt', 'Energy and Utilities', 5, 'active'),
('Media', 'الإعلام', 'fa-tv', 'Media and Broadcasting', 6, 'active');

INSERT INTO brands (sector_id, name, name_ar, category, category_ar, description, description_ar, icon, logo_color, logo_color_secondary, display_order, status) VALUES
-- Finance Brands
(1, 'Al-Ahli Bank', 'البنك الأهلي', 'Banking Service', 'الخدمات المصرفية', 'Digital marketing and financial services', 'استراتيجيات التسويق الرقمي والخدمات المصرفية', 'fa-landmark', '#1a365d', '#2c5aa0', 1, 'active'),
(1, 'Etlaq Investment', 'إطلاق استثمار', 'Investment Management', 'إدارة الاستثمارات', 'Training programs and investment consulting', 'برامج التدريب والاستشارات الاستثمارية', 'fa-piggy-bank', '#c41e2e', '#8b0000', 2, 'active'),
(1, 'Payment Company', 'شركة المدفوعات', 'Electronic Payments', 'الدفع الإلكتروني', 'Payment gateway development and financial solutions', 'تطوير منصات الدفع والحلول المالية', 'fa-credit-card', '#0066cc', '#003366', 3, 'active'),

-- Technology Brands
(2, 'TechNum Soft', 'تكنوم سوفت', 'Software Development', 'تطوير البرمجيات', 'Customized software and application solutions', 'حلول البرمجيات المخصصة والتطبيقات', 'fa-code', '#0066cc', '#3399ff', 1, 'active'),
(2, 'Advanced AI', 'الذكاء الاصطناعي المتقدم', 'Artificial Intelligence', 'الذكاء الاصطناعي', 'AI and machine learning solutions', 'حلول الذكاء الاصطناعي والتعلم الآلي', 'fa-robot', '#ff6b00', '#ff9900', 2, 'active'),
(2, 'Cloud Services', 'كلاود سبيس', 'Cloud Computing', 'الحوسبة السحابية', 'Cloud services and hosting solutions', 'خدمات الحوسبة السحابية والاستضافة', 'fa-cloud', '#0099ff', '#00ccff', 3, 'active'),

-- Retail Brands
(3, 'Al-Barq Stores', 'متاجر البرق', 'Retail and Stores', 'التجزئة والمتاجر', 'Marketing strategies and distribution', 'استراتيجيات التسويق والتوزيع', 'fa-store', '#ff3333', '#cc0000', 1, 'active'),
(3, 'Arab E-Commerce', 'التجارة الإلكترونية العربية', 'E-commerce', 'التسوق الإلكتروني', 'E-commerce platforms and sales', 'منصات التجارة الإلكترونية والمبيعات', 'fa-shopping-cart', '#0066ff', '#0033cc', 2, 'active'),
(3, 'Offers and Deals', 'العروض والخصومات', 'Loyalty Programs', 'برامج الولاء', 'Promotional offers and loyalty programs', 'برامج العروض التروجية والولاء', 'fa-gift', '#ffcc00', '#ff9900', 3, 'active'),

-- Healthcare Brands
(4, 'Al-Noor Hospital', 'مستشفى النور', 'Healthcare Services', 'الخدمات الصحية', 'Medical marketing and public relations', 'التسويق الطبي والعلاقات العامة', 'fa-heart', '#00aa00', '#008800', 1, 'active'),
(4, 'Modern Medicines', 'الأدوية الحديثة', 'Pharmacy', 'الصيدلة', 'Pharmaceutical marketing and products', 'تسويق الأدوية والمنتجات الصيدلانية', 'fa-pills', '#1a7a2a', '#0d4d17', 2, 'active'),
(4, 'Fitness Centers', 'مراكز اللياقة البدنية', 'Health and Fitness', 'الصحة واللياقة', 'Fitness center management and health', 'إدارة مراكز اللياقة والعافية', 'fa-dumbbell', '#00ccaa', '#009980', 3, 'active'),

-- Energy Brands
(5, 'East Energy', 'طاقة الشرق', 'Oil and Gas', 'النفط والغاز', 'Industrial marketing and public relations', 'التسويق الصناعي والعلاقات العامة', 'fa-fire', '#cc3300', '#ff6600', 1, 'active'),
(5, 'Future Solar Energy', 'الطاقة الشمسية المستقبل', 'Renewable Energy', 'الطاقة المتجددة', 'Clean energy solutions and sustainability', 'حلول الطاقة النظيفة والمستدامة', 'fa-sun', '#ffaa00', '#ff8800', 2, 'active'),
(5, 'Green Energy', 'الطاقة الخضراء', 'Sustainability', 'الاستدامة', 'Sustainable energy project development', 'تطوير مشاريع الطاقة المستدامة', 'fa-leaf', '#00aa44', '#008833', 3, 'active'),

-- Media Brands
(6, 'News Channel', 'القناة الإخبارية', 'News and Broadcasting', 'الأخبار والإعلام', 'Media production and publishing', 'الإنتاج الإعلامي والنشر الرقمي', 'fa-tv', '#cc0000', '#990000', 1, 'active'),
(6, 'Arab Podcast', 'بودكاست العرب', 'Podcast Production', 'إنتاج البودكاست', 'Podcast content production and publishing', 'إنتاج ونشر محتوى البودكاست', 'fa-podcast', '#0099ff', '#0066cc', 2, 'active'),
(6, 'Film Production', 'الإنتاج السينمائي', 'Films and Series', 'الأفلام والمسلسلات', 'Film and media project production', 'إنتاج وتسويق المشاريع الفنية', 'fa-film', '#ff6600', '#ff9900', 3, 'active');
