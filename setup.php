<?php
/**
 * Database Setup & Initialization Script
 * Run once to set up the complete database with sample data
 * Access via: http://localhost/setup.php or php setup.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine if running from CLI or web
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><meta charset="UTF-8"><title>Database Setup</title><style>';
    echo 'body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;direction:rtl}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}';
    echo 'h1{color:#08137b;border-bottom:3px solid #08137b;padding-bottom:10px}';
    echo '.success{color:#27ae60;font-weight:bold}';
    echo '.error{color:#e74c3c;font-weight:bold}';
    echo '.warning{color:#f39c12;font-weight:bold}';
    echo '.info{background:#ecf0f1;padding:10px;border-radius:4px;margin:10px 0;border-left:4px solid #3498db}';
    echo 'ul{list-style:none;padding:0}li{padding:8px 0;border-bottom:1px solid #ecf0f1}';
    echo '</style></head><body><div class="container">';
    echo '<h1>⚙️ إعداد قاعدة البيانات</h1>';
    echo '<p>جاري إعداد النظام...</p>';
}

try {
    // Include config
    require_once 'config.php';
    
    if (!isset($db)) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    $messages = [];
    
    // ===================================
    // Drop Old Tables (properly handle foreign keys)
    // ===================================
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Drop in reverse order of dependencies
    $db->exec("DROP TABLE IF EXISTS brands");
    $db->exec("DROP TABLE IF EXISTS sectors");
    $db->exec("DROP TABLE IF EXISTS articles");
    $db->exec("DROP TABLE IF EXISTS contact_messages");
    $db->exec("DROP TABLE IF EXISTS activity_log");
    $db->exec("DROP TABLE IF EXISTS site_settings");
    $db->exec("DROP TABLE IF EXISTS services");
    $db->exec("DROP TABLE IF EXISTS admin_users");
    
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // ===================================
    // Create Core Tables
    // ===================================
    
    // 1. Admin Users
    $db->exec("CREATE TABLE admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول admin_users';
    
    // 2. Site Settings
    $db->exec("CREATE TABLE site_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value LONGTEXT,
        is_public BOOLEAN DEFAULT FALSE,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول site_settings';
    
    // 3. Services
    $db->exec("CREATE TABLE services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        icon VARCHAR(100) DEFAULT 'fa-star',
        price_min DECIMAL(10,2) DEFAULT 0,
        price_max DECIMAL(10,2) DEFAULT 0,
        duration VARCHAR(100),
        display_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول services';
    
    // 4. Articles
    $db->exec("CREATE TABLE articles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        excerpt TEXT,
        content LONGTEXT,
        image_url VARCHAR(500),
        category VARCHAR(100) DEFAULT 'news',
        badge VARCHAR(50),
        author_id INT NOT NULL,
        word_count INT DEFAULT 0,
        reading_time INT DEFAULT 0,
        publish_date DATE,
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_category (category),
        INDEX idx_author (author_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول articles';
    
    // 5. Contact Messages
    $db->exec("CREATE TABLE contact_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        service VARCHAR(100),
        subject VARCHAR(255),
        message LONGTEXT,
        admin_reply LONGTEXT NULL,
        status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_email (email),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول contact_messages';
    
    // 6. Activity Log
    $db->exec("CREATE TABLE activity_log (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT,
        action_type VARCHAR(50),
        action_description VARCHAR(255),
        table_name VARCHAR(100),
        record_id INT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin (admin_id),
        INDEX idx_action (action_type),
        INDEX idx_table (table_name),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول activity_log';
    
    // 7. Sectors
    $db->exec("CREATE TABLE sectors (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول sectors';
    
    // 8. Brands
    $db->exec("CREATE TABLE brands (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = '✓ تم إنشاء جدول brands';
    
    // ===================================
    // Insert Sample Data
    // ===================================
    
    // Insert Admin User
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $db->prepare("
        INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
        VALUES (:username, :email, :password, :name, :role, :status)
    ")->execute([
        ':username' => 'admin',
        ':email' => 'admin@aboelmajdhub.website',
        ':password' => $adminPassword,
        ':name' => 'إدارة النظام',
        ':role' => 'admin',
        ':status' => 'active'
    ]);
    $adminId = $db->lastInsertId();
    $messages[] = '✓ تم إضافة حساب المسؤول (كلمة المرور: admin123)';
    
    // Insert Site Settings
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'site_email', ':value' => 'info@aboelmajdhub.website', ':public' => 1, ':desc' => 'البريد الرسمي للموقع']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'site_phone', ':value' => '+966536789012', ':public' => 1, ':desc' => 'رقم الهاتف الرسمي']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'whatsapp_number', ':value' => '+966536789012', ':public' => 1, ':desc' => 'رقم واتس أب']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'facebook_url', ':value' => 'https://facebook.com', ':public' => 1, ':desc' => 'رابط الفيسبوك']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'instagram_url', ':value' => 'https://instagram.com', ':public' => 1, ':desc' => 'رابط الإنستجرام']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'linkedin_url', ':value' => 'https://linkedin.com', ':public' => 1, ':desc' => 'رابط لينكدإن']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'youtube_url', ':value' => 'https://youtube.com', ':public' => 1, ':desc' => 'رابط يوتيوب']);
    
    $db->prepare("INSERT INTO site_settings (setting_key, setting_value, is_public, description) VALUES (:key, :value, :public, :desc)")
        ->execute([':key' => 'twitter_url', ':value' => 'https://twitter.com', ':public' => 1, ':desc' => 'رابط تويتر']);
    
    $messages[] = '✓ تم إضافة إعدادات الموقع';
    
    // Insert Services
    $services = [
        ['استراتيجيات التسويق', 'تطوير استراتيجيات تسويقية متقدمة وفعالة', 'fa-chart-line'],
        ['إدارة الحملات الإعلانية', 'إدارة احترافية لحملاتك الإعلانية', 'fa-bullhorn'],
        ['إدارة وسائل التواصل', 'تطوير المحتوى وإدارة منصات التواصل', 'fa-users'],
        ['التدريب والاستشارات', 'تقديم برامج تدريبية واستشارية متخصصة', 'fa-graduation-cap'],
        ['التصميم والإنتاج', 'إنتاج محتوى بصري عالي الجودة', 'fa-palette'],
        ['الأمن السيبراني', 'حماية شاملة لأمن المعلومات والبيانات', 'fa-shield-alt']
    ];
    
    foreach ($services as $idx => $service) {
        $db->prepare("
            INSERT INTO services (title, description, icon, display_order, status)
            VALUES (:title, :desc, :icon, :order, :status)
        ")->execute([
            ':title' => $service[0],
            ':desc' => $service[1],
            ':icon' => $service[2],
            ':order' => $idx + 1,
            ':status' => 'active'
        ]);
    }
    $messages[] = '✓ تم إضافة الخدمات الأساسية';
    
    // Insert Articles
    $articles = [
        ['مستقبل التسويق الرقمي', 'future-of-digital-marketing', 'استكشف أحدث التطورات في التسويق الرقمي والذكاء الاصطناعي...', '<p>التسويق الرقمي يشهد تطورات سريعة ومتلاحقة...</p>', 'news', 'تدوينة'],
        ['نصائح أساسية للمشاريع الناشئة', 'startup-tips', 'اكتشف أهم النصائح لنجاح مشروعك الناشئ...', '<p>بدء مشروع جديد يتطلب تخطيطاً جيداً...</p>', 'tips', 'نصيحة'],
        ['تحليل السوق العربي', 'arab-market-analysis', 'فهم عميق لسلوك المستهلك العربي...', '<p>السوق العربي يتمتع بمواصفات فريدة...</p>', 'analysis', 'تحليل']
    ];
    
    foreach ($articles as $idx => $article) {
        $now = date('Y-m-d');
        $db->prepare("
            INSERT INTO articles (title, slug, excerpt, content, category, badge, author_id, publish_date, status, word_count, reading_time)
            VALUES (:title, :slug, :excerpt, :content, :category, :badge, :author, :date, :status, :words, :time)
        ")->execute([
            ':title' => $article[0],
            ':slug' => $article[1],
            ':excerpt' => $article[2],
            ':content' => $article[3],
            ':category' => $article[4],
            ':badge' => $article[5],
            ':author' => $adminId,
            ':date' => $now,
            ':status' => 'published',
            ':words' => 500 + ($idx * 100),
            ':time' => 3 + $idx
        ]);
    }
    $messages[] = '✓ تم إضافة مقالات العينة';
    
    // Insert Sectors
    $sectors = [
        ['Finance', 'المالية والبنوك', 'fa-university'],
        ['Technology', 'التكنولوجيا', 'fa-laptop'],
        ['Retail', 'التجزئة', 'fa-shopping-bag'],
        ['Healthcare', 'الصحة', 'fa-hospital'],
        ['Energy', 'الطاقة', 'fa-bolt'],
        ['Media', 'الإعلام', 'fa-tv']
    ];
    
    foreach ($sectors as $idx => $sector) {
        $db->prepare("
            INSERT INTO sectors (name, name_ar, icon, display_order, status)
            VALUES (:name, :name_ar, :icon, :order, :status)
        ")->execute([
            ':name' => $sector[0],
            ':name_ar' => $sector[1],
            ':icon' => $sector[2],
            ':order' => $idx + 1,
            ':status' => 'active'
        ]);
    }
    $messages[] = '✓ تم إضافة القطاعات';
    
    // Insert Brands (for each sector)
    $brands = [
        ['البنك الأهلي', 'National Bank', 'المالية', 'Banking', '#1F77B4', '#FF7F0E', 'fa-building-columns', 1],
        ['SAP', 'SAP Systems', 'البرمجيات', 'Software', '#2CA02C', '#D62728', 'fa-code', 2],
        ['أمازون', 'Amazon', 'التجارة الإلكترونية', 'E-commerce', '#9467BD', '#8C564B', 'fa-shopping-cart', 3],
        ['مستشفى الملك فهد', 'King Fahad Hospital', 'الصحة', 'Healthcare', '#E377C2', '#7F7F7F', 'fa-hospital', 4],
        ['أرامكو', 'Saudi Aramco', 'الطاقة', 'Energy', '#BCBD22', '#17BECF', 'fa-oil-can', 5],
        ['BBC', 'BBC', 'البث الإعلامي', 'Broadcasting', '#FF9896', '#9467BD', 'fa-tv', 6]
    ];
    
    // Get sector IDs
    $sectors = $db->query("SELECT id FROM sectors ORDER BY display_order ASC")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($brands as $idx => $brand) {
        $sectorId = isset($sectors[$idx]) ? $sectors[$idx] : 1;
        $db->prepare("
            INSERT INTO brands (sector_id, name_ar, name, category_ar, category, description_ar, description, icon, logo_color, logo_color_secondary, display_order, status)
            VALUES (:sector, :name_ar, :name, :cat_ar, :cat, :desc_ar, :desc, :icon, :color1, :color2, :order, :status)
        ")->execute([
            ':sector' => $sectorId,
            ':name_ar' => $brand[0],
            ':name' => $brand[1],
            ':cat_ar' => $brand[2],
            ':cat' => $brand[3],
            ':desc_ar' => 'شركة رائدة في مجالها',
            ':desc' => 'Leading company in its field',
            ':icon' => $brand[6],
            ':color1' => $brand[4],
            ':color2' => $brand[5],
            ':order' => $idx + 1,
            ':status' => 'active'
        ]);
    }
    $messages[] = '✓ تم إضافة العلامات التجارية';
    
    // Output success
    if (!$isCLI) {
        echo '<h2 style="color:#27ae60">✓ تم الإعداد بنجاح!</h2>';
        echo '<div class="info"><strong>معلومات المسؤول:</strong><br>اسم المستخدم: <strong>admin</strong><br>كلمة المرور: <strong>admin123</strong></div>';
        echo '<div class="info"><strong>معلومات الاتصال:</strong><br>البريد: info@aboelmajdhub.website<br>الهاتف: +966536789012</div>';
        echo '<h3>عمليات تم إجراؤها:</h3><ul>';
        foreach ($messages as $msg) {
            echo '<li><span class="success">' . htmlspecialchars($msg) . '</span></li>';
        }
        echo '</ul>';
        echo '<p style="margin-top:30px;padding-top:20px;border-top:2px solid #ecf0f1;">يمكنك الآن الذهاب إلى <a href="/" style="color:#08137b">الصفحة الرئيسية</a> أو <a href="/admin/login.php" style="color:#08137b">لوحة التحكم</a></p>';
        echo '</div></body></html>';
    } else {
        echo "✓ Database setup completed successfully!\n";
        foreach ($messages as $msg) {
            echo $msg . "\n";
        }
        echo "\nAdmin credentials:\nUsername: admin\nPassword: admin123\n";
    }
    
} catch (Exception $e) {
    if (!$isCLI) {
        echo '<h2 style="color:#e74c3c">✗ خطأ في الإعداد</h2>';
        echo '<div class="info" style="background:#ffe6e6"><strong class="error">رسالة الخطأ:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '</div></body></html>';
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit(1);
}
?>
