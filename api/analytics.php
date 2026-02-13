<?php
/**
 * Analytics API
 * Track page views, events, user behavior
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Track page view
    if ($action === 'track_pageview') {
        $page_type = $_POST['page_type'] ?? null;
        $page_id = $_POST['page_id'] ?? null;
        $page_title = $_POST['page_title'] ?? null;
        $view_duration = $_POST['view_duration'] ?? 0;

        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = $_COOKIE['session_id'] ?? uniqid();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;

        $stmt = $db->prepare("
            INSERT INTO analytics_page_views 
            (page_type, page_id, page_title, user_id, session_id, ip_address, user_agent, referrer, view_duration)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $page_type, $page_id, $page_title, $user_id, $session_id,
            $ip_address, $user_agent, $referrer, $view_duration
        ]);

        return jsonResponse(['success' => true, 'session_id' => $session_id]);
    }

    // Track custom event
    if ($action === 'track_event') {
        $event_name = $_POST['event_name'] ?? null;
        $event_category = $_POST['event_category'] ?? null;
        $event_data = $_POST['event_data'] ?? null;

        if (!$event_name) throw new Exception('Missing event_name');

        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = $_COOKIE['session_id'] ?? null;

        $stmt = $db->prepare("
            INSERT INTO analytics_events (event_name, event_category, user_id, session_id, event_data)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $event_name, $event_category, $user_id, $session_id, 
            $event_data ? json_encode($event_data) : null
        ]);

        return jsonResponse(['success' => true]);
    }

    // Get dashboard stats (admin only)
    if ($action === 'get_stats') {
        if (!isAdmin()) return unauthorized();

        $days = $_GET['days'] ?? 30;
        $date_from = date('Y-m-d', strtotime("-$days days"));

        // Total visits
        $visitsStmt = $db->prepare("
            SELECT COUNT(DISTINCT session_id) as total_visits 
            FROM analytics_page_views 
            WHERE created_at >= ?
        ");
        $visitsStmt->execute([$date_from]);
        $visits = $visitsStmt->fetch();

        // Total page views
        $pvStmt = $db->prepare("
            SELECT COUNT(*) as total_pv FROM analytics_page_views WHERE created_at >= ?
        ");
        $pvStmt->execute([$date_from]);
        $pv = $pvStmt->fetch();

        // Top pages
        $topPagesStmt = $db->prepare("
            SELECT page_type, page_title, COUNT(*) as views 
            FROM analytics_page_views
            WHERE created_at >= ?
            GROUP BY page_type, page_id
            ORDER BY views DESC
            LIMIT 10
        ");
        $topPagesStmt->execute([$date_from]);
        $topPages = $topPagesStmt->fetchAll();

        // Top events
        $topEventsStmt = $db->prepare("
            SELECT event_name, COUNT(*) as count 
            FROM analytics_events
            WHERE created_at >= ?
            GROUP BY event_name
            ORDER BY count DESC
            LIMIT 10
        ");
        $topEventsStmt->execute([$date_from]);
        $topEvents = $topEventsStmt->fetchAll();

        // Daily stats
        $dailyStmt = $db->prepare("
            SELECT * FROM analytics_daily_stats 
            WHERE stat_date >= ?
            ORDER BY stat_date DESC
        ");
        $dailyStmt->execute([$date_from]);
        $daily = $dailyStmt->fetchAll();

        return jsonResponse([
            'total_visits' => $visits['total_visits'] ?? 0,
            'total_page_views' => $pv['total_pv'] ?? 0,
            'top_pages' => $topPages,
            'top_events' => $topEvents,
            'daily_stats' => $daily
        ]);
    }

    // Get user analytics
    if ($action === 'get_user_analytics') {
        $user_id = $_GET['user_id'] ?? null;
        if (!isAdmin()) return unauthorized();

        if (!$user_id) throw new Exception('Missing user_id');

        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT session_id) as total_sessions,
                   COUNT(*) as total_views,
                   SUM(view_duration) as total_duration,
                   MAX(created_at) as last_visit
            FROM analytics_page_views
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);

        return jsonResponse($stmt->fetch());
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
