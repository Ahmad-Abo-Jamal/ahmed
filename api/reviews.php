<?php
/**
 * Reviews & Ratings API
 * User reviews for products, articles, services
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Get reviews
    if ($action === 'get_reviews') {
        $reviewable_type = $_GET['reviewable_type'] ?? null;
        $reviewable_id = $_GET['reviewable_id'] ?? null;
        $limit = $_GET['limit'] ?? 50;

        if (!$reviewable_type || !$reviewable_id) {
            throw new Exception('Missing reviewable_type or reviewable_id');
        }

        $stmt = $db->prepare("
            SELECT r.*, u.full_name, u.avatar_url,
                   (SELECT COUNT(*) FROM review_helpfulness WHERE review_id = r.id AND is_helpful = TRUE) as helpful_votes
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.reviewable_type = ? AND r.reviewable_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$reviewable_type, $reviewable_id, $limit]);
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get single review
    if ($action === 'get_review') {
        $review_id = $_GET['review_id'] ?? null;
        if (!$review_id) throw new Exception('Missing review_id');

        $stmt = $db->prepare("
            SELECT r.*, u.full_name, u.avatar_url FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ? AND r.status = 'approved'
        ");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();

        if (!$review) throw new Exception('Review not found', 404);

        // Get review images
        $imgStmt = $db->prepare("SELECT image_url FROM review_images WHERE review_id = ?");
        $imgStmt->execute([$review_id]);
        $review['images'] = $imgStmt->fetchAll();

        return jsonResponse($review);
    }

    // Create review
    if ($action === 'create_review') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $reviewable_type = $_POST['reviewable_type'] ?? null;
        $reviewable_id = $_POST['reviewable_id'] ?? null;
        $title_ar = $_POST['title_ar'] ?? null;
        $content_ar = $_POST['content_ar'] ?? null;
        $rating = $_POST['rating'] ?? null;

        if (!$reviewable_type || !$reviewable_id || !$rating) {
            throw new Exception('Missing required fields');
        }

        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5');
        }

        // Check verification
        $is_verified = false;
        if ($reviewable_type === 'product') {
            $orderStmt = $db->prepare("
                SELECT COUNT(*) as count FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
            ");
            $orderStmt->execute([$_SESSION['user_id'], $reviewable_id]);
            $is_verified = $orderStmt->fetch()['count'] > 0;
        }

        // Create review
        $stmt = $db->prepare("
            INSERT INTO reviews (user_id, reviewable_type, reviewable_id, title_ar, content_ar, rating, is_verified_purchase)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], $reviewable_type, $reviewable_id,
            $title_ar, $content_ar, $rating, $is_verified
        ]);

        $review_id = $db->lastInsertId();

        // Update rating average
        $ratingStmt = $db->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews
            WHERE reviewable_type = ? AND reviewable_id = ? AND status = 'approved'
        ");
        $ratingStmt->execute([$reviewable_type, $reviewable_id]);
        $ratingData = $ratingStmt->fetch();

        if ($reviewable_type === 'product') {
            $updateStmt = $db->prepare("UPDATE products SET rating_average = ?, rating_count = ? WHERE id = ?");
        } else {
            $updateStmt = $db->prepare("UPDATE $reviewable_type SET rating_average = ?, rating_count = ? WHERE id = ?");
        }
        $updateStmt->execute([$ratingData['avg_rating'] ?? 0, $ratingData['count'] ?? 0, $reviewable_id]);

        return jsonResponse(['success' => true, 'review_id' => $review_id]);
    }

    // Mark helpful
    if ($action === 'mark_helpful') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $review_id = $_POST['review_id'] ?? null;
        $is_helpful = $_POST['is_helpful'] ?? true;

        if (!$review_id) throw new Exception('Missing review_id');

        $stmt = $db->prepare("
            INSERT INTO review_helpfulness (review_id, user_id, is_helpful)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_helpful = ?
        ");
        $stmt->execute([$review_id, $_SESSION['user_id'], $is_helpful, $is_helpful]);

        return jsonResponse(['success' => true]);
    }

    // Approve review (admin only)
    if ($action === 'approve_review') {
        if (!isAdmin()) return unauthorized();

        $review_id = $_POST['review_id'] ?? null;
        if (!$review_id) throw new Exception('Missing review_id');

        $stmt = $db->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$review_id]);

        return jsonResponse(['success' => true]);
    }

    // Get pending reviews (admin only)
    if ($action === 'get_pending') {
        if (!isAdmin()) return unauthorized();

        $stmt = $db->prepare("
            SELECT r.*, u.full_name FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();

        return jsonResponse($stmt->fetchAll());
    }

    throw new Exception('Invalid action');

} catch (Exception $e) {
    return errorResponse($e->getMessage(), 400);
}

function jsonResponse($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function unauthorized() {
    return errorResponse('Unauthorized', 403);
}
