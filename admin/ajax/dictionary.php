<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch($action) {
        case 'add_word':
            $word_ar = sanitize(trim($_POST['word_ar'] ?? ''));
            $definition_ar = sanitize(trim($_POST['definition_ar'] ?? ''));
            $pronunciation = sanitize(trim($_POST['pronunciation'] ?? ''));
            $word_type = sanitize($_POST['word_type'] ?? 'noun');
            $category = sanitize(trim($_POST['category'] ?? ''));
            $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
            $is_featured = (int)($_POST['is_featured'] ?? 0);
            $synonyms = sanitize(trim($_POST['synonyms'] ?? ''));
            $antonyms = sanitize(trim($_POST['antonyms'] ?? ''));
            $examples = json_encode(array_filter(explode("\n", $_POST['examples'] ?? '')));
            
            if (!$word_ar || !$definition_ar) {
                throw new Exception('يجب إدخال الكلمة والتعريف');
            }
            
            $stmt = $db->prepare("INSERT INTO dictionary (word_ar, definition_ar, pronunciation, word_type, category, difficulty_level, is_featured, synonyms, antonyms, examples, usage_count) 
                                VALUES (:word_ar, :definition_ar, :pronunciation, :word_type, :category, :difficulty_level, :is_featured, :synonyms, :antonyms, :examples, 0)");
            $stmt->execute([
                ':word_ar' => $word_ar,
                ':definition_ar' => $definition_ar,
                ':pronunciation' => $pronunciation,
                ':word_type' => $word_type,
                ':category' => $category,
                ':difficulty_level' => $difficulty_level,
                ':is_featured' => $is_featured,
                ':synonyms' => $synonyms,
                ':antonyms' => $antonyms,
                ':examples' => $examples
            ]);
            
            logActivity($db, $_SESSION['admin_id'], 'create', 'إضافة كلمة: ' . $word_ar, 'dictionary', $db->lastInsertId());
            
            echo json_encode(['success' => true, 'message' => 'تمت إضافة الكلمة بنجاح']);
            break;
            
        case 'edit_word':
            $id = (int)($_POST['id'] ?? 0);
            $word_ar = sanitize(trim($_POST['word_ar'] ?? ''));
            $definition_ar = sanitize(trim($_POST['definition_ar'] ?? ''));
            $pronunciation = sanitize(trim($_POST['pronunciation'] ?? ''));
            $word_type = sanitize($_POST['word_type'] ?? 'noun');
            $category = sanitize(trim($_POST['category'] ?? ''));
            $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
            $is_featured = (int)($_POST['is_featured'] ?? 0);
            $synonyms = sanitize(trim($_POST['synonyms'] ?? ''));
            $antonyms = sanitize(trim($_POST['antonyms'] ?? ''));
            $examples = json_encode(array_filter(explode("\n", $_POST['examples'] ?? '')));
            
            if (!$word_ar || !$definition_ar) {
                throw new Exception('يجب إدخال الكلمة والتعريف');
            }
            
            // Verify word exists
            $stmt = $db->prepare("SELECT * FROM dictionary WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                throw new Exception('الكلمة غير موجودة');
            }
            
            $stmt = $db->prepare("UPDATE dictionary SET word_ar = :word_ar, definition_ar = :definition_ar, pronunciation = :pronunciation, word_type = :word_type, category = :category, difficulty_level = :difficulty_level, is_featured = :is_featured, synonyms = :synonyms, antonyms = :antonyms, examples = :examples WHERE id = :id");
            $stmt->execute([
                ':word_ar' => $word_ar,
                ':definition_ar' => $definition_ar,
                ':pronunciation' => $pronunciation,
                ':word_type' => $word_type,
                ':category' => $category,
                ':difficulty_level' => $difficulty_level,
                ':is_featured' => $is_featured,
                ':synonyms' => $synonyms,
                ':antonyms' => $antonyms,
                ':examples' => $examples,
                ':id' => $id
            ]);
            
            logActivity($db, $_SESSION['admin_id'], 'update', 'تعديل كلمة: ' . $word_ar, 'dictionary', $id);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث الكلمة بنجاح']);
            break;
            
        case 'delete_word':
            $id = (int)($_POST['id'] ?? 0);
            
            $stmt = $db->prepare("SELECT * FROM dictionary WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $word = $stmt->fetch();
            if (!$word) {
                throw new Exception('الكلمة غير موجودة');
            }
            
            $stmt = $db->prepare("DELETE FROM dictionary WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
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
