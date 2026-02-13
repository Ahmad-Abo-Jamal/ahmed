<?php
/**
 * Database Migration Script
 * Upload to root, visit http://yoursite.com/migrate.php
 * Auto-deletes after running
 */

// Security: Check if run from console only
if (php_sapi_name() === 'cli') {
    echo "✓ Running database migrations...\n";
} else {
    // Web access - require confirmation
    if (empty($_GET['confirm'])) {
        die('<h2>Database Migration</h2><p>This will add new columns for articles (reading_time, word_count) and create sectors/brands tables.</p><a href="?confirm=1" style="background:#08137b;color:#fff;padding:10px 15px;border-radius:6px;text-decoration:none">Run Migration</a>');
    }
}

try {
    require_once 'config.php';
    
    if (!isset($db)) {
        throw new Exception('Database not connected');
    }

    $migrations = [
        'articles_upgrade' => [
            "ALTER TABLE articles ADD COLUMN IF NOT EXISTS word_count INT DEFAULT 0",
            "ALTER TABLE articles ADD COLUMN IF NOT EXISTS reading_time INT DEFAULT 0",
            "UPDATE articles SET word_count = CHAR_LENGTH(content) - CHAR_LENGTH(REPLACE(content, ' ', '')) + 1 WHERE word_count = 0",
            "UPDATE articles SET reading_time = CEIL(word_count / 200.0) WHERE reading_time = 0",
        ],
        'messages_upgrade' => [
            "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS thread_id INT DEFAULT NULL",
            "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS parent_message_id INT NULL",
            "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS admin_notes LONGTEXT",
            "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS read_at DATETIME NULL",
            "ALTER TABLE contact_messages MODIFY COLUMN admin_reply LONGTEXT NULL",
            "CREATE TABLE IF NOT EXISTS message_conversations (id INT PRIMARY KEY AUTO_INCREMENT, thread_id INT UNIQUE NOT NULL, contact_email VARCHAR(100) NOT NULL, contact_name VARCHAR(100) NOT NULL, subject VARCHAR(255) NOT NULL, status ENUM('open', 'waiting', 'resolved', 'closed') DEFAULT 'open', assigned_to INT NULL, priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal', last_message_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_thread (thread_id), INDEX idx_status (status), INDEX idx_assigned (assigned_to), INDEX idx_created (created_at), FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ],
        'sectors_brands' => [
            "CREATE TABLE IF NOT EXISTS sectors (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255) NOT NULL, name_ar VARCHAR(255) NOT NULL, icon VARCHAR(100) DEFAULT 'fa-briefcase', description TEXT, display_order INT DEFAULT 0, status ENUM('active', 'inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_status (status), INDEX idx_order (display_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS brands (id INT PRIMARY KEY AUTO_INCREMENT, sector_id INT NOT NULL, name VARCHAR(255) NOT NULL, name_ar VARCHAR(255) NOT NULL, category VARCHAR(255), category_ar VARCHAR(255), description TEXT, description_ar TEXT, icon VARCHAR(100) DEFAULT 'fa-star', logo_url VARCHAR(500), logo_color VARCHAR(7) DEFAULT '#08137b', logo_color_secondary VARCHAR(7) DEFAULT '#4f09a7', display_order INT DEFAULT 0, status ENUM('active', 'inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_sector (sector_id), INDEX idx_status (status), INDEX idx_order (display_order), FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "INSERT IGNORE INTO sectors (name, name_ar, icon, description, display_order, status) VALUES ('Finance', 'المالية والبنوك', 'fa-university', 'Banking and Financial Services', 1, 'active'), ('Technology', 'التكنولوجيا', 'fa-laptop', 'Technology and Software Solutions', 2, 'active'), ('Retail', 'التجزئة', 'fa-shopping-bag', 'Retail and E-commerce', 3, 'active'), ('Healthcare', 'الصحة', 'fa-hospital', 'Healthcare and Medical Services', 4, 'active'), ('Energy', 'الطاقة', 'fa-bolt', 'Energy and Utilities', 5, 'active'), ('Media', 'الإعلام', 'fa-tv', 'Media and Broadcasting', 6, 'active')"
        ]
    ];

    $results = [];
    foreach ($migrations as $name => $queries) {
        foreach ($queries as $sql) {
            try {
                $db->exec($sql);
                $results[] = "✓ {$name}: OK";
            } catch (Exception $e) {
                $results[] = "⚠ {$name}: " . $e->getMessage();
            }
        }
    }

    if (php_sapi_name() === 'cli') {
        foreach ($results as $r) echo $r . "\n";
        echo "\n✓ All migrations completed!\n";
    } else {
        echo '<style>body{font-family:Arial;padding:20px}h2{color:#08137b}.success{color:#27ae60}.warning{color:#f39c12}li{margin:8px 0}</style>';
        echo '<h2>✓ Migrations Completed</h2><ul>';
        foreach ($results as $r) echo '<li class="' . (strpos($r, '✓') === 0 ? 'success' : 'warning') . '">' . htmlspecialchars($r) . '</li>';
        echo '</ul><p style="color:#666;margin-top:20px"><strong>This file will auto-delete in 5 seconds...</strong></p>';
        echo '<script>setTimeout(function(){ fetch("migrate.php?delete=1"); setTimeout(function(){ window.location.href = "/"; }, 1000); }, 5000);</script>';
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "✗ Error: " . $e->getMessage() . "\n";
    } else {
        echo '<h2 style="color:#e74c3c">✗ Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    exit(1);
}

// Auto-delete this file
if (!empty($_GET['delete'])) {
    @unlink(__FILE__);
    exit;
}

?>
