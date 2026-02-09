<?php
/**
 * API: Get Articles
 * Endpoint: /api/articles.php
 * Method: GET
 * Returns: JSON list of published articles
 */

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Error handling
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    require_once '../config.php';
    
    // Check if database is connected
    if (!isset($db)) {
        throw new Exception('Database not connected');
    }
    
    // If requesting a single article (by id or slug) and full content, return it
    $full = isset($_GET['full']) && $_GET['full'] == '1';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

    if ($full && ($id || $slug)) {
        if ($id) {
            $stmt = $db->prepare("SELECT a.*, u.full_name as author FROM articles a JOIN admin_users u ON a.author_id = u.id WHERE a.id = :id AND a.status = 'published' LIMIT 1");
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $db->prepare("SELECT a.*, u.full_name as author FROM articles a JOIN admin_users u ON a.author_id = u.id WHERE a.slug = :slug AND a.status = 'published' LIMIT 1");
            $stmt->execute([':slug' => $slug]);
        }

        $article = $stmt->fetch();
        if ($article) {
            // compute word count and reading time if not present
            $content = strip_tags($article['content']);
            $words = str_word_count($content);
            $article['word_count'] = intval($article['word_count'] ?? $words);
            $article['reading_time'] = intval($article['reading_time'] ?? ceil($words / 200));
            echo json_encode(['success' => true, 'article' => $article], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'المقال غير موجود'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Get published articles (list)
    $stmt = $db->prepare(""
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.excerpt,
            a.category,
            a.badge,
            a.image_url,
            a.views,
            a.publish_date,
            a.created_at,
            u.full_name as author,
            a.word_count,
            a.reading_time
        FROM articles a
        JOIN admin_users u ON a.author_id = u.id
        WHERE a.status = 'published'
        ORDER BY a.publish_date DESC
        LIMIT 200
    """);
    
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    // Format dates
    foreach ($articles as &$article) {
        if ($article['publish_date']) {
            $article['publish_date'] = date('Y-m-d', strtotime($article['publish_date']));
        }
        $article['views'] = intval($article['views']);
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($articles),
        'articles' => $articles
    ], JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}