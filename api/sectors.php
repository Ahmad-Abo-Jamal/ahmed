<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    // Get all sectors with their brands
    $sectors = $db->query("SELECT * FROM sectors WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
    
    $result = [];
    
    foreach ($sectors as $sector) {
        $brands = $db->query("SELECT * FROM brands WHERE sector_id = ? AND status = 'active' ORDER BY display_order ASC", [$sector['id']])->fetchAll();
        
        // Convert database keys to frontend-compatible keys
        $sectorData = [
            'id' => $sector['id'],
            'name' => $sector['name'],
            'name_ar' => $sector['name_ar'],
            'icon' => $sector['icon'],
            'display_order' => $sector['display_order'],
            'brands' => array_map(function($brand) {
                return [
                    'id' => $brand['id'],
                    'name' => $brand['name'],
                    'name_ar' => $brand['name_ar'],
                    'category' => $brand['category'],
                    'category_ar' => $brand['category_ar'],
                    'description' => $brand['description'],
                    'description_ar' => $brand['description_ar'],
                    'icon' => $brand['icon'],
                    'logo_url' => $brand['logo_url'],
                    'logo_color' => $brand['logo_color'],
                    'logo_color_secondary' => $brand['logo_color_secondary'],
                    'display_order' => $brand['display_order']
                ];
            }, $brands)
        ];
        
        $result[] = $sectorData;
    }
    
    echo json_encode([
        'success' => true,
        'sectors' => $result
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>
