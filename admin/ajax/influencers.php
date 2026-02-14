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
            $stmt = $db->prepare("SELECT * FROM influencers ORDER BY is_featured DESC, follower_count DESC LIMIT 500");
            $stmt->execute();
            $influencers = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $influencers;
            break;

        case 'get':
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM influencers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $influencer = $stmt->fetch();
            if ($influencer) {
                $response['success'] = true;
                $response['data'] = $influencer;
            } else {
                $response['message'] = 'المؤثر غير موجود';
            }
            break;

        case 'add':
            $name_ar = sanitize($_POST['name_ar'] ?? '');
            $name_en = sanitize($_POST['name_en'] ?? '');
            $category = sanitize($_POST['category'] ?? '');
            $category_ar = sanitize($_POST['category_ar'] ?? '');
            $platform = sanitize($_POST['platform'] ?? '');
            $platform_url = filter_var($_POST['platform_url'] ?? '', FILTER_VALIDATE_URL);
            $follower_count = intval($_POST['follower_count'] ?? 0);
            $engagement_rate = floatval($_POST['engagement_rate'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $verification_status = sanitize($_POST['verification_status'] ?? 'unverified');

            if (empty($name_ar) || empty($platform) || !$platform_url) {
                $response['message'] = 'يرجى ملء جميع الحقول المطلوبة';
                break;
            }

            $stmt = $db->prepare("
                INSERT INTO influencers (name_ar, name_en, category, category_ar, platform, platform_url, 
                                        follower_count, engagement_rate, is_featured, verification_status, created_at)
                VALUES (:name_ar, :name_en, :category, :category_ar, :platform, :platform_url,
                        :follower_count, :engagement_rate, :is_featured, :verification_status, NOW())
            ");
            
            if ($stmt->execute([
                ':name_ar' => $name_ar,
                ':name_en' => $name_en,
                ':category' => $category,
                ':category_ar' => $category_ar,
                ':platform' => $platform,
                ':platform_url' => $platform_url,
                ':follower_count' => $follower_count,
                ':engagement_rate' => $engagement_rate,
                ':is_featured' => $is_featured,
                ':verification_status' => $verification_status
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'influencer_added', "إضافة مؤثر: $name_ar", 'influencers', $db->lastInsertId());
                $response['success'] = true;
                $response['message'] = 'تم إضافة المؤثر بنجاح';
            }
            break;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $name_ar = sanitize($_POST['name_ar'] ?? '');
            $name_en = sanitize($_POST['name_en'] ?? '');
            $category = sanitize($_POST['category'] ?? '');
            $category_ar = sanitize($_POST['category_ar'] ?? '');
            $platform = sanitize($_POST['platform'] ?? '');
            $platform_url = filter_var($_POST['platform_url'] ?? '', FILTER_VALIDATE_URL);
            $follower_count = intval($_POST['follower_count'] ?? 0);
            $engagement_rate = floatval($_POST['engagement_rate'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $verification_status = sanitize($_POST['verification_status'] ?? 'unverified');

            if (empty($name_ar) || empty($platform)) {
                $response['message'] = 'يرجى ملء الحقول المطلوبة';
                break;
            }

            $stmt = $db->prepare("
                UPDATE influencers SET 
                name_ar = :name_ar,
                name_en = :name_en,
                category = :category,
                category_ar = :category_ar,
                platform = :platform,
                platform_url = :platform_url,
                follower_count = :follower_count,
                engagement_rate = :engagement_rate,
                is_featured = :is_featured,
                verification_status = :verification_status
                WHERE id = :id
            ");
            
            if ($stmt->execute([
                ':id' => $id,
                ':name_ar' => $name_ar,
                ':name_en' => $name_en,
                ':category' => $category,
                ':category_ar' => $category_ar,
                ':platform' => $platform,
                ':platform_url' => $platform_url,
                ':follower_count' => $follower_count,
                ':engagement_rate' => $engagement_rate,
                ':is_featured' => $is_featured,
                ':verification_status' => $verification_status
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'influencer_updated', "تعديل مؤثر: $name_ar", 'influencers', $id);
                $response['success'] = true;
                $response['message'] = 'تم تحديث المؤثر بنجاح';
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT name_ar FROM influencers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $influencer = $stmt->fetch();
            
            if (!$influencer) {
                $response['message'] = 'المؤثر غير موجود';
                break;
            }

            $del = $db->prepare("DELETE FROM influencers WHERE id = :id");
            if ($del->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'influencer_deleted', "حذف مؤثر: {$influencer['name_ar']}", 'influencers', $id);
                $response['success'] = true;
                $response['message'] = 'تم حذف المؤثر بنجاح';
            }
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
