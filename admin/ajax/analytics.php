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
        case 'daily_stats':
            $days = intval($_GET['days'] ?? 30);
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as visits,
                    COUNT(DISTINCT session_id) as unique_sessions
                FROM analytics_page_views 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([':days' => $days]);
            $stats = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $stats;
            break;

        case 'page_stats':
            $stmt = $db->prepare("
                SELECT 
                    page_type,
                    page_title,
                    COUNT(*) as total_views,
                    AVG(view_duration) as avg_duration
                FROM analytics_page_views
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY page_type, page_title
                ORDER BY total_views DESC
                LIMIT 20
            ");
            $stmt->execute();
            $pages = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $pages;
            break;

        case 'device_stats':
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                        ELSE 'Desktop'
                    END AS device_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT session_id) as unique_users,
                    AVG(view_duration) as avg_duration
                FROM analytics_page_views
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY device_type
            ");
            $stmt->execute();
            $devices = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $devices;
            break;

        case 'summary':
            // Get summary statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(DISTINCT session_id) as total_sessions,
                    COUNT(*) as total_page_views,
                    AVG(view_duration) as avg_session_duration
                FROM analytics_page_views
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $views = $stmt->fetch();

            $stmt = $db->prepare("
                SELECT COUNT(*) as total_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $users = $stmt->fetch();

            $stmt = $db->prepare("
                SELECT COUNT(*) as total_articles FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $articles = $stmt->fetch();

            $response['success'] = true;
            $response['data'] = [
                'total_sessions' => $views['total_sessions'] ?? 0,
                'total_page_views' => $views['total_page_views'] ?? 0,
                'avg_duration' => round($views['avg_session_duration'] ?? 0),
                'new_users' => $users['total_users'] ?? 0,
                'new_articles' => $articles['total_articles'] ?? 0
            ];
            break;

        case 'top_referrers':
            $stmt = $db->prepare("
                SELECT 
                    referrer,
                    COUNT(*) as count,
                    COUNT(DISTINCT session_id) as unique_sessions
                FROM analytics_page_views
                WHERE referrer IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY referrer
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $referrers = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $referrers;
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
