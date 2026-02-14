<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch($action) {
        case 'add_brand':
            $sector_id = (int)$_POST['sector_id'];
            $name_ar = trim($_POST['name_ar'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category_ar = trim($_POST['category_ar'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description_ar = trim($_POST['description_ar'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-star');
            $logo_url = trim($_POST['logo_url'] ?? '');
            $logo_color = trim($_POST['logo_color'] ?? '#08137b');
            $logo_color_secondary = trim($_POST['logo_color_secondary'] ?? '#4f09a7');
            $status = $_POST['status'] ?? 'active';
            
            if (!$sector_id || !$name_ar || !$name) {
                throw new Exception('يجب إدخال القطاع والاسم بالعربية والإنجليزية');
            }
            
            // Verify sector exists
            $stmt = $db->prepare("SELECT * FROM sectors WHERE id = ?");
            $stmt->execute([$sector_id]);
            $sector = $stmt->fetch();
            if (!$sector) {
                throw new Exception('القطاع غير موجود');
            }
            
            $stmt = $db->prepare("INSERT INTO brands (sector_id, name_ar, name, category_ar, category, description_ar, description, icon, logo_url, logo_color, logo_color_secondary, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sector_id, $name_ar, $name, $category_ar, $category, $description_ar, $description, $icon, $logo_url, $logo_color, $logo_color_secondary, $status]);
            
            logActivity($db, $_SESSION['admin_id'], 'create', 'إضافة علامة تجارية: ' . $name_ar, 'brands', $db->lastInsertId());
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة العلامة التجارية بنجاح']);
            break;
            
        case 'edit_brand':
            $id = (int)$_POST['id'];
            $sector_id = (int)$_POST['sector_id'];
            $name_ar = trim($_POST['name_ar'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category_ar = trim($_POST['category_ar'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description_ar = trim($_POST['description_ar'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-star');
            $logo_url = trim($_POST['logo_url'] ?? '');
            $logo_color = trim($_POST['logo_color'] ?? '#08137b');
            $logo_color_secondary = trim($_POST['logo_color_secondary'] ?? '#4f09a7');
            $status = $_POST['status'] ?? 'active';
            
            if (!$sector_id || !$name_ar || !$name) {
                throw new Exception('يجب إدخال القطاع والاسم بالعربية والإنجليزية');
            }
            
            // Verify brand exists
            $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $brand = $stmt->fetch();
            if (!$brand) {
                throw new Exception('العلامة التجارية غير موجودة');
            }
            
            $stmt = $db->prepare("UPDATE brands SET sector_id = ?, name_ar = ?, name = ?, category_ar = ?, category = ?, description_ar = ?, description = ?, icon = ?, logo_url = ?, logo_color = ?, logo_color_secondary = ?, status = ? WHERE id = ?");
            $stmt->execute([$sector_id, $name_ar, $name, $category_ar, $category, $description_ar, $description, $icon, $logo_url, $logo_color, $logo_color_secondary, $status, $id]);
            
            logActivity($db, $_SESSION['admin_id'], 'update', 'تعديل العلامة التجارية: ' . $name_ar, 'brands', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث العلامة التجارية بنجاح']);
            break;
            
        case 'delete_brand':
            $id = (int)$_POST['id'];
            
            $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $brand = $stmt->fetch();
            if (!$brand) {
                throw new Exception('العلامة التجارية غير موجودة');
            }
            
            $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($db, $_SESSION['admin_id'], 'delete', 'حذف العلامة التجارية: ' . $brand['name_ar'], 'brands', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف العلامة التجارية بنجاح']);
            break;
            
        case 'get_brand':
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $brand = $stmt->fetch();
            
            if (!$brand) {
                throw new Exception('العلامة التجارية غير موجودة');
            }
            
            echo json_encode(['success' => true, 'brand' => $brand]);
            break;
            
        default:
            throw new Exception('إجراء غير صحيح');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات']);
}
?>
