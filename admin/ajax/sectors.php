<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_sector':
            $name_ar = trim($_POST['name_ar'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-briefcase');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            if (!$name_ar || !$name) {
                throw new Exception('يجب إدخال الاسم بالعربية والإنجليزية');
            }
            
            $stmt = $db->prepare("INSERT INTO sectors (name_ar, name, icon, description, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name_ar, $name, $icon, $description, $status]);
            
            logActivity($db, $_SESSION['admin_id'], 'create', 'إضافة قطاع جديد: ' . $name_ar, 'sectors', $db->lastInsertId());
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة القطاع بنجاح']);
            break;
            
        case 'edit_sector':
            $id = (int)$_POST['id'];
            $name_ar = trim($_POST['name_ar'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-briefcase');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            if (!$name_ar || !$name) {
                throw new Exception('يجب إدخال الاسم بالعربية والإنجليزية');
            }
            
            $stmt = $db->prepare("UPDATE sectors SET name_ar = ?, name = ?, icon = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$name_ar, $name, $icon, $description, $status, $id]);
            
            logActivity($db, $_SESSION['admin_id'], 'update', 'تعديل القطاع: ' . $name_ar, 'sectors', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث القطاع بنجاح']);
            break;
            
        case 'delete_sector':
            $id = (int)$_POST['id'];
            
            $sector = $db->query("SELECT * FROM sectors WHERE id = ?", [$id])->fetch();
            if (!$sector) {
                throw new Exception('القطاع غير موجود');
            }
            
            $stmt = $db->prepare("DELETE FROM sectors WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($db, $_SESSION['admin_id'], 'delete', 'حذف القطاع: ' . $sector['name_ar'], 'sectors', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف القطاع وجميع العلامات المرتبطة به']);
            break;
            
        case 'get_sector':
            $id = (int)$_GET['id'];
            $sector = $db->query("SELECT * FROM sectors WHERE id = ?", [$id])->fetch();
            
            if (!$sector) {
                throw new Exception('القطاع غير موجود');
            }
            
            echo json_encode(['success' => true, 'sector' => $sector]);
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
