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
    
    // Get published articles
    $stmt = $db->prepare("
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
            u.full_name as author
        FROM articles a
        JOIN admin_users u ON a.author_id = u.id
        WHERE a.status = 'published'
        ORDER BY a.publish_date DESC
        LIMIT 50
    ");
    
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