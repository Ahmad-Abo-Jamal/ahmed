<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch($action) {
        case 'add_word':
            $word_ar = trim($_POST['word_ar'] ?? '');
            $definition = trim($_POST['definition'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
            $is_featured = (int)($_POST['is_featured'] ?? 0);
            
            if (!$word_ar) {
                throw new Exception('يجب إدخال الكلمة');
            }
            
            $stmt = $db->prepare("INSERT INTO dictionary (word_ar, definition, category, difficulty_level, is_featured, usage_count) 
                                VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$word_ar, $definition, $category, $difficulty_level, $is_featured]);
            
            logActivity($db, $_SESSION['admin_id'], 'create', 'إضافة كلمة: ' . $word_ar, 'dictionary', $db->lastInsertId());
            
            echo json_encode(['success' => true, 'message' => 'تمت إضافة الكلمة بنجاح']);
            break;
            
        case 'edit_word':
            $id = (int)($_POST['id'] ?? 0);
            $word_ar = trim($_POST['word_ar'] ?? '');
            $definition = trim($_POST['definition'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
            $is_featured = (int)($_POST['is_featured'] ?? 0);
            
            if (!$word_ar) {
                throw new Exception('يجب إدخال الكلمة');
            }
            
            // Verify word exists
            $stmt = $db->prepare("SELECT * FROM dictionary WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('الكلمة غير موجودة');
            }
            
            $stmt = $db->prepare("UPDATE dictionary SET word_ar = ?, definition = ?, category = ?, difficulty_level = ?, is_featured = ? WHERE id = ?");
            $stmt->execute([$word_ar, $definition, $category, $difficulty_level, $is_featured, $id]);
            
            logActivity($db, $_SESSION['admin_id'], 'update', 'تعديل كلمة: ' . $word_ar, 'dictionary', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث الكلمة بنجاح']);
            break;
            
        case 'delete_word':
            $id = (int)($_POST['id'] ?? 0);
            
            $stmt = $db->prepare("SELECT * FROM dictionary WHERE id = ?");
            $stmt->execute([$id]);
            $word = $stmt->fetch();
            if (!$word) {
                throw new Exception('الكلمة غير موجودة');
            }
            
            $stmt = $db->prepare("DELETE FROM dictionary WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($db, $_SESSION['admin_id'], 'delete', 'حذف كلمة: ' . $word['word_ar'], 'dictionary', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف الكلمة بنجاح']);
            break;
            
        case 'get_word':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM dictionary WHERE id = ?");
            $stmt->execute([$id]);
            $word = $stmt->fetch();
            
            if (!$word) {
                throw new Exception('الكلمة غير موجودة');
            }
            
            echo json_encode(['success' => true, 'word' => $word]);
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
