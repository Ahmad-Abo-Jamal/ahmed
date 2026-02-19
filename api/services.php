<?php
/**
 * API: Get Services
 * Endpoint: /api/services.php
 * Method: GET
 * Returns: JSON list of active services
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Get active services
    $stmt = $db->prepare("
        SELECT 
            id,
            title,
            description,
            icon,
            display_order,
            price_min,
            price_max,
            duration
        FROM services
        WHERE status = 'active'
        ORDER BY display_order ASC
    ");
    $stmt->execute();
    
    $services = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'count' => count($services),
        'services' => $services
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات'
    ], JSON_UNESCAPED_UNICODE);
}
