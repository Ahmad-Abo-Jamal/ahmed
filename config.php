<?php
// ===================================
// Database Configuration
// ===================================
// Use environment variables if available, fallback to defined values
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 's736913_abo');
define('DB_PASS', getenv('DB_PASS') ?: 'D8onEzfF');
define('DB_NAME', getenv('DB_NAME') ?: 's736913_abo');

// ===================================
// Site Configuration 
// ===================================
define('SITE_URL', 'http://aboelmajdhub.website');
define('ADMIN_URL', SITE_URL . '/admin/index.php');

// ===================================
// Error Reporting (Disable in production)
// ===================================
if (getenv('ENVIRONMENT') === 'production' || (!getenv('DEBUG') && $_SERVER['HTTP_HOST'] !== 'localhost')) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ===================================
// Session Configuration
// ===================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Enable secure cookies only when the request is HTTPS (avoid breaking local/dev HTTP)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
          (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $secure ? 1 : 0);
session_start();

// ===================================
// Database Connection
// ===================================
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    header('Location: /admin/errors/500.php');
    exit;
}

// ===================================
// Helper Functions
// ===================================
function logActivity($db, $admin_id, $action_type, $description, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO activity_log (admin_id, action_type, action_description, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (:admin_id, :action_type, :description, :table_name, :record_id, :old_values, :new_values, :ip, :user_agent)
        ");
        $stmt->execute([
            ':admin_id' => $admin_id,
            ':action_type' => $action_type,
            ':description' => $description,
            ':table_name' => $table_name,
            ':record_id' => $record_id,
            ':old_values' => $old_values ? json_encode($old_values) : null,
            ':new_values' => $new_values ? json_encode($new_values) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch(PDOException $e) {
        // Silent fail for activity log
    }
}

function getSetting($db, $key, $default = '') {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

function updateSetting($db, $key, $value) {
    try {
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
        return $stmt->execute([':value' => $value, ':key' => $key]);
    } catch(PDOException $e) {
        return false;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/errors/401.php');
        exit;
    }
}

function getAdminInfo($db, $admin_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id AND status = 'active'");
        $stmt->execute([':id' => $admin_id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}