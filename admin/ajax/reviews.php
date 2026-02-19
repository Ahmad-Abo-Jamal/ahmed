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
            $status = $_GET['status'] ?? '';
            $query = "SELECT id, user_id, reviewable_type, reviewable_id, title_ar, rating, status, helpful_count, created_at FROM reviews ORDER BY created_at DESC LIMIT 500";
            
            if ($status) {
                $stmt = $db->prepare("SELECT id, user_id, reviewable_type, reviewable_id, title_ar, rating, status, helpful_count, created_at FROM reviews WHERE status = :status ORDER BY created_at DESC LIMIT 500");
                $stmt->execute([':status' => $status]);
            } else {
                $stmt = $db->prepare($query);
                $stmt->execute();
            }
            
            $reviews = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $reviews;
            break;

        case 'get':
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM reviews WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $review = $stmt->fetch();
            if ($review) {
                $response['success'] = true;
                $response['data'] = $review;
            } else {
                $response['message'] = 'التقييم غير موجود';
            }
            break;

        case 'approve':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("UPDATE reviews SET status = 'approved' WHERE id = :id");
            if ($stmt->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'review_approved', "موافقة على تقييم ID: $id", 'reviews', $id);
                $response['success'] = true;
                $response['message'] = 'تم الموافقة على التقييم';
            }
            break;

        case 'reject':
            $id = intval($_POST['id'] ?? 0);
            $admin_notes = sanitize($_POST['admin_notes'] ?? '');
            $stmt = $db->prepare("UPDATE reviews SET status = 'rejected', admin_notes = :notes WHERE id = :id");
            if ($stmt->execute([':id' => $id, ':notes' => $admin_notes])) {
                logActivity($db, $_SESSION['admin_id'], 'review_rejected', "رفض تقييم ID: $id", 'reviews', $id);
                $response['success'] = true;
                $response['message'] = 'تم رفض التقييم';
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT title_ar FROM reviews WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $review = $stmt->fetch();
            
            if (!$review) {
                $response['message'] = 'التقييم غير موجود';
                break;
            }

            $del = $db->prepare("DELETE FROM reviews WHERE id = :id");
            if ($del->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'review_deleted', "حذف تقييم: {$review['title_ar']}", 'reviews', $id);
                $response['success'] = true;
                $response['message'] = 'تم حذف التقييم بنجاح';
            }
            break;

        case 'stats':
            // Get review statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reviews,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reviews,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reviews,
                    AVG(CAST(rating as DECIMAL(3,2))) as average_rating
                FROM reviews
            ");
            $stmt->execute();
            $stats = $stmt->fetch();
            $response['success'] = true;
            $response['data'] = $stats;
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
