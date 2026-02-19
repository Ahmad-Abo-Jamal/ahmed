<?php
/**
 * API: Get Site Settings
 * Endpoint: /api/settings.php
 * Method: GET
 * Returns: Public site settings
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Get only public settings
    $stmt = $db->prepare("
        SELECT 
            setting_key,
            setting_value
        FROM site_settings
        WHERE is_public = TRUE
    ");
    $stmt->execute();
    
    $settings = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات'
    ], JSON_UNESCAPED_UNICODE);
}
