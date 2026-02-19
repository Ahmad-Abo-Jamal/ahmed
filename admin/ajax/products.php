<?php
require_once '../../config.php';

if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'create':
            $name_ar = sanitize($_POST['name_ar'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $category_id = intval($_POST['category_id'] ?? 0);
            $description_ar = sanitize($_POST['description_ar'] ?? '');
            $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
            $discount_percent = floatval($_POST['discount_percent'] ?? 0);
            $cost_price = floatval($_POST['cost_price'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;

            if (!$name_ar || $price <= 0) {
                throw new Exception('اسم المنتج والسعر مطلوبان');
            }

            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $name_ar), '-'));
            
            $stmt = $db->prepare("
                INSERT INTO products 
                (name_ar, slug, price, category_id, description_ar, stock_quantity, discount_percent, cost_price, is_featured, created_by, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            if ($stmt->execute([$name_ar, $slug, $price, $category_id, $description_ar, $stock_quantity, $discount_percent, $cost_price, $is_featured, $_SESSION['admin_id']])) {
                $product_id = $db->lastInsertId();
                
                // Handle image upload
                if (!empty($_FILES['image'])) {
                    $image = uploadImage($_FILES['image'], 'products');
                    if ($image) {
                        $updateStmt = $db->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                        $updateStmt->execute([$image, $product_id]);
                    }
                }
                
                logActivity($db, $_SESSION['admin_id'], 'product_created', "إضافة منتج: $name_ar", 'products', $product_id);
                $response['success'] = true;
                $response['message'] = 'تم إضافة المنتج بنجاح';
                $response['product_id'] = $product_id;
            }
            break;

        case 'update':
            $product_id = intval($_POST['id'] ?? 0);
            $name_ar = sanitize($_POST['name_ar'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            
            if (!$product_id || !$name_ar || $price <= 0) {
                throw new Exception('البيانات مطلوبة');
            }

            $stmt = $db->prepare("
                UPDATE products SET 
                name_ar = ?, price = ?, category_id = ?, 
                description_ar = ?, stock_quantity = ?, discount_percent = ?, 
                cost_price = ?, is_featured = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $name_ar, 
                $price, 
                intval($_POST['category_id'] ?? 0),
                sanitize($_POST['description_ar'] ?? ''),
                intval($_POST['stock_quantity'] ?? 0),
                floatval($_POST['discount_percent'] ?? 0),
                floatval($_POST['cost_price'] ?? 0),
                isset($_POST['is_featured']) ? 1 : 0,
                $product_id
            ])) {
                // Handle image upload
                if (!empty($_FILES['image'])) {
                    $image = uploadImage($_FILES['image'], 'products');
                    if ($image) {
                        $updateStmt = $db->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                        $updateStmt->execute([$image, $product_id]);
                    }
                }
                
                logActivity($db, $_SESSION['admin_id'], 'product_updated', "تحديث منتج ID: $product_id", 'products', $product_id);
                $response['success'] = true;
                $response['message'] = 'تم تحديث المنتج بنجاح';
            }
            break;

        case 'delete':
            $product_id = intval($_POST['id'] ?? 0);
            if (!$product_id) throw new Exception('معرف المنتج مطلوب');

            $stmt = $db->prepare("SELECT name_ar FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $response['message'] = 'المنتج غير موجود';
                break;
            }

            $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
            if ($delStmt->execute([$product_id])) {
                logActivity($db, $_SESSION['admin_id'], 'product_deleted', "حذف منتج: {$product['name_ar']}", 'products', $product_id);
                $response['success'] = true;
                $response['message'] = 'تم حذف المنتج بنجاح';
            }
            break;

        case 'toggle_active':
            $product_id = intval($_POST['id'] ?? 0);
            if (!$product_id) throw new Exception('معرف المنتج مطلوب');

            $stmt = $db->prepare("SELECT is_active FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $newStatus = $product['is_active'] ? 0 : 1;
                $updateStmt = $db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
                if ($updateStmt->execute([$newStatus, $product_id])) {
                    logActivity($db, $_SESSION['admin_id'], 'product_toggled', "تغيير حالة منتج ID: $product_id", 'products', $product_id);
                    $response['success'] = true;
                    $response['message'] = 'تم تحديث الحالة';
                    $response['is_active'] = $newStatus;
                }
            }
            break;

        case 'add_image':
            $product_id = intval($_POST['product_id'] ?? 0);
            if (!$product_id || empty($_FILES['image'])) {
                throw new Exception('معرف المنتج والصورة مطلوبان');
            }

            $image_url = uploadImage($_FILES['image'], 'products');
            if ($image_url) {
                $stmt = $db->prepare("
                    INSERT INTO product_images (product_id, image_url, alt_text_ar)
                    VALUES (?, ?, ?)
                ");
                if ($stmt->execute([$product_id, $image_url, sanitize($_POST['alt_text'] ?? '')])) {
                    $response['success'] = true;
                    $response['message'] = 'تم إضافة الصورة بنجاح';
                    $response['image_url'] = $image_url;
                }
            }
            break;

        case 'delete_image':
            $image_id = intval($_POST['image_id'] ?? 0);
            if (!$image_id) throw new Exception('معرف الصورة مطلوب');

            $stmt = $db->prepare("SELECT image_url FROM product_images WHERE id = ?");
            $stmt->execute([$image_id]);
            $image = $stmt->fetch();
            
            if ($image) {
                $delStmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
                if ($delStmt->execute([$image_id])) {
                    // Delete file if needed
                    $response['success'] = true;
                    $response['message'] = 'تم حذف الصورة بنجاح';
                }
            }
            break;

        case 'get':
            $product_id = intval($_GET['id'] ?? 0);
            if (!$product_id) throw new Exception('معرف المنتج مطلوب');

            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Get product images
                $imgStmt = $db->prepare("SELECT id, image_url, alt_text_ar FROM product_images WHERE product_id = ? ORDER BY display_order");
                $imgStmt->execute([$product_id]);
                $product['images'] = $imgStmt->fetchAll();
                
                $response['success'] = true;
                $response['data'] = $product;
            }
            break;

        case 'list':
            $category_id = $_GET['category_id'] ?? '';
            $search = sanitize($_GET['search'] ?? '');
            
            $sql = "SELECT id, name_ar, price, stock_quantity, is_active, is_featured, image_url FROM products WHERE 1=1";
            
            if ($category_id) {
                $sql .= " AND category_id = " . intval($category_id);
            }
            if ($search) {
                $sql .= " AND (name_ar LIKE '%" . $db->real_escape_string($search) . "%')";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 500";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $products;
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

function uploadImage($file, $folder = 'products') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed) || $file['size'] > 5000000) {
        throw new Exception('صيغة الملف غير مدعومة أو الحجم كبير جداً');
    }
    
    $newName = 'img-' . uniqid() . '.' . $ext;
    $uploadDir = "/uploads/$folder/";
    $uploadPath = __DIR__ . '/../../' . $uploadDir . $newName;
    
    @mkdir(dirname($uploadPath), 0755, true);
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $uploadDir . $newName;
    } else {
        throw new Exception('فشل رفع الملف');
    }
}
?>
