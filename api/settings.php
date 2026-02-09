<?php
/**
 * API: Get Site Settings
 * Endpoint: /api/settings.php
 * Method: GET
 * Returns: Public site settings
 */

require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Get only public settings
    $stmt = $db->query("
        SELECT 
            setting_key,
            setting_value
        FROM site_settings
        WHERE is_public = TRUE
    ");
    
    $settings = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم'
    ], JSON_UNESCAPED_UNICODE);
}
