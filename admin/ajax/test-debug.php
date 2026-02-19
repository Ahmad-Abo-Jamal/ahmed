<?php
require_once '../../config.php';
header('Content-Type: application/json');

// SECURITY: Require admin authentication
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Debug endpoint to verify AJAX path and session/auth status
try {
    $debug = [
        'reachable' => true,
        'time' => date('c'),
        'isAdmin' => isAdmin()
    ];

    echo json_encode(['success' => true, 'debug' => $debug], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
