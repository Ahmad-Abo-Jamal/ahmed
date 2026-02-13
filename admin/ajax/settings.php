<?php
// Disable output buffering
if (ob_get_level()) ob_end_clean();

require_once '../../config.php';

// Check if user is admin
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'save':
            $updated = [];
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $stmt = $db->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
                    $stmt->execute([':value' => sanitize($value), ':key' => $key]);
                    $updated[$key] = $stmt->rowCount();
                }
            }
            logActivity($db, $_SESSION['admin_id'], 'settings_update', 'تحديث الإعدادات');
            // Return current settings for verification
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            $settings = $stmt->fetchAll();
            $response = ['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح', 'updated' => $updated, 'settings' => $settings];
            break;
            
        case 'change_password':
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            
            // Get admin info
            $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            // Verify current password (plain text comparison)
            if ($current !== $admin['password']) {
                $response['message'] = 'كلمة المرور الحالية غير صحيحة';
                break;
            }
            
            // Update password (plain text)
            $stmt = $db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $new, ':id' => $_SESSION['admin_id']]);
            
            logActivity($db, $_SESSION['admin_id'], 'password_change', 'تغيير كلمة المرور');
            $response = ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
            break;
            
        default:
            $response['message'] = 'إجراء غير معروف';
    }
} catch(PDOException $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);