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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'list':
            $stmt = $db->prepare("SELECT a.id,a.title,a.slug,a.excerpt,a.category,a.image_url,a.status,a.publish_date,a.reading_time,u.full_name as author FROM articles a JOIN admin_users u ON a.author_id = u.id ORDER BY created_at DESC LIMIT 500");
            $stmt->execute();
            $rows = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            exit;

        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM articles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $article = $stmt->fetch();
            if ($article) {
                $response = ['success' => true, 'data' => $article];
            } else {
                $response['message'] = 'المقال غير موجود';
            }
            break;
            
        case 'create':
            $title = sanitize($_POST['title'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $excerpt = sanitize($_POST['excerpt'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'article';
            $badge = sanitize($_POST['badge'] ?? '');
            $image_url = sanitize($_POST['image_url'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            
            if (empty($title) || empty($slug) || empty($content)) {
                $response['message'] = 'يرجى ملء جميع الحقول المطلوبة';
                break;
            }
            
            // compute word count and reading time (approx 200 wpm)
            $plain = strip_tags($content);
            $words = str_word_count($plain);
            $reading_time = max(1, intval(ceil($words / 200)));

            $stmt = $db->prepare("
                INSERT INTO articles (title, slug, excerpt, content, category, badge, image_url, author_id, status, publish_date, word_count, reading_time)
                VALUES (:title, :slug, :excerpt, :content, :category, :badge, :image_url, :author_id, :status, NOW(), :word_count, :reading_time)
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':excerpt' => $excerpt,
                ':content' => $content,
                ':category' => $category,
                ':badge' => $badge,
                ':image_url' => $image_url,
                ':author_id' => $_SESSION['admin_id'],
                ':status' => $status,
                ':word_count' => $words,
                ':reading_time' => $reading_time
            ]);
            
            logActivity($db, $_SESSION['admin_id'], 'article_create', "إضافة مقال: {$title}", 'articles', $db->lastInsertId());
            $response = ['success' => true, 'message' => 'تم إضافة المقال بنجاح'];
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $excerpt = sanitize($_POST['excerpt'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $category = $_POST['category'] ?? 'article';
            $badge = sanitize($_POST['badge'] ?? '');
            $image_url = sanitize($_POST['image_url'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            
            // compute word count and reading time
            $plain = strip_tags($content);
            $words = str_word_count($plain);
            $reading_time = max(1, intval(ceil($words / 200)));

            $stmt = $db->prepare("
                UPDATE articles SET 
                title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                category = :category, badge = :badge, image_url = :image_url, status = :status,
                word_count = :word_count, reading_time = :reading_time
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':excerpt' => $excerpt,
                ':content' => $content,
                ':category' => $category,
                ':badge' => $badge,
                ':image_url' => $image_url,
                ':status' => $status,
                ':word_count' => $words,
                ':reading_time' => $reading_time,
                ':id' => $id
            ]);
            
            logActivity($db, $_SESSION['admin_id'], 'article_update', "تحديث مقال: {$title}", 'articles', $id);
            $response = ['success' => true, 'message' => 'تم تحديث المقال بنجاح'];
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT title FROM articles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $article = $stmt->fetch();
            
            if ($article) {
                $delete = $db->prepare("DELETE FROM articles WHERE id = :id");
                $delete->execute([':id' => $id]);
                logActivity($db, $_SESSION['admin_id'], 'article_delete', "حذف مقال: {$article['title']}", 'articles', $id);
                $response = ['success' => true, 'message' => 'تم حذف المقال بنجاح'];
            } else {
                $response['message'] = 'المقال غير موجود';
            }
            break;
            
        default:
            $response['message'] = 'إجراء غير معروف';
    }
} catch(PDOException $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);