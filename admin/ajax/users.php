<?php
require_once '../../config.php';

if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'list':
            $stmt = $db->prepare("SELECT id, full_name, email, phone, country, email_verified, newsletter_subscribed, status, created_at FROM users ORDER BY created_at DESC LIMIT 500");
            $stmt->execute();
            $users = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $users;
            break;

        case 'get':
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if ($user) {
                $response['success'] = true;
                $response['data'] = $user;
            } else {
                $response['message'] = 'المستخدم غير موجود';
            }
            break;

        case 'add':
            $full_name = sanitize($_POST['full_name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $phone = sanitize($_POST['phone'] ?? '');
            $country = sanitize($_POST['country'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($full_name) || !$email || empty($password)) {
                $response['message'] = 'يرجى ملء جميع الحقول المطلوبة';
                break;
            }

            // Check if email already exists
            $check = $db->prepare("SELECT id FROM users WHERE email = :email");
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $response['message'] = 'البريد الإلكتروني موجود بالفعل';
                break;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, phone, country, password, status, created_at) 
                VALUES (:full_name, :email, :phone, :country, :password, 'active', NOW())
            ");
            
            if ($stmt->execute([
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':country' => $country,
                ':password' => $hashed_password
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'user_added', "إضافة مستخدم: $full_name", 'users', $db->lastInsertId());
                $response['success'] = true;
                $response['message'] = 'تم إضافة المستخدم بنجاح';
            }
            break;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $full_name = sanitize($_POST['full_name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $phone = sanitize($_POST['phone'] ?? '');
            $country = sanitize($_POST['country'] ?? '');
            $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

            if (empty($full_name) || !$email) {
                $response['message'] = 'يرجى ملء الحقول المطلوبة';
                break;
            }

            $stmt = $db->prepare("
                UPDATE users SET 
                full_name = :full_name,
                email = :email,
                phone = :phone,
                country = :country,
                status = :status
                WHERE id = :id
            ");
            
            if ($stmt->execute([
                ':id' => $id,
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':country' => $country,
                ':status' => $status
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'user_updated', "تعديل مستخدم: $full_name", 'users', $id);
                $response['success'] = true;
                $response['message'] = 'تم تحديث المستخدم بنجاح';
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT full_name FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $response['message'] = 'المستخدم غير موجود';
                break;
            }

            $del = $db->prepare("DELETE FROM users WHERE id = :id");
            if ($del->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'user_deleted', "حذف مستخدم: {$user['full_name']}", 'users', $id);
                $response['success'] = true;
                $response['message'] = 'تم حذف المستخدم بنجاح';
            }
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
