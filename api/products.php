<?php
/**
 * E-Commerce API
 * Products, Cart, Orders management
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Get all products
    if ($action === 'list_products') {
        $category_id = $_GET['category_id'] ?? null;
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;
        
        $sql = "SELECT id, name_ar, slug, description_ar, image_url, price, discount_percent, stock_quantity, is_featured FROM products WHERE is_active = TRUE";
        if ($category_id) $sql .= " AND category_id = " . intval($category_id);
        $sql .= " ORDER BY is_featured DESC, created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return jsonResponse($stmt->fetchAll());
    }

    // Get single product
    if ($action === 'get_product') {
        $product_id = $_GET['product_id'] ?? null;
        if (!$product_id) throw new Exception('Missing product_id');

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) throw new Exception('Product not found', 404);

        // Get product images
        $imgStmt = $db->prepare("SELECT image_url, alt_text_ar FROM product_images WHERE product_id = ? ORDER BY display_order");
        $imgStmt->execute([$product_id]);
        $product['images'] = $imgStmt->fetchAll();

        // Get product reviews
        $revStmt = $db->prepare("
            SELECT r.*, u.full_name FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.reviewable_type = 'product' AND r.reviewable_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC LIMIT 10
        ");
        $revStmt->execute([$product_id]);
        $product['reviews'] = $revStmt->fetchAll();

        return jsonResponse($product);
    }

    // Create product (admin only)
    if ($action === 'create_product') {
        if (!isAdmin()) return unauthorized();

        $name_ar = $_POST['name_ar'] ?? null;
        $price = $_POST['price'] ?? null;
        $category_id = $_POST['category_id'] ?? null;
        
        if (!$name_ar || !$price) throw new Exception('Missing required fields');

        $slug = sanitizeSlug($name_ar);
        $stmt = $db->prepare("
            INSERT INTO products (name_ar, slug, category_id, price, description_ar, stock_quantity, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name_ar, $slug, $category_id, $price,
            $_POST['description_ar'] ?? null,
            $_POST['stock_quantity'] ?? 0,
            $_SESSION['admin_id']
        ]);
        
        return jsonResponse(['success' => true, 'product_id' => $db->lastInsertId()]);
    }

    // Add to cart
    if ($action === 'add_to_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $product_id = $_POST['product_id'] ?? null;
        $quantity = $_POST['quantity'] ?? 1;

        if (!$product_id) throw new Exception('Missing product_id');

        // Check stock
        $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock');
        }

        $stmt = $db->prepare("
            INSERT INTO shopping_cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
        
        return jsonResponse(['success' => true, 'message' => 'Added to cart']);
    }

    // Get cart
    if ($action === 'get_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $stmt = $db->prepare("
            SELECT sc.id, sc.product_id, sc.quantity, p.name_ar, p.price, p.discount_percent, p.image_url
            FROM shopping_cart sc
            JOIN products p ON sc.product_id = p.id
            WHERE sc.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return jsonResponse($stmt->fetchAll());
    }

    // Remove from cart
    if ($action === 'remove_from_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $cart_id = $_POST['cart_id'] ?? null;
        if (!$cart_id) throw new Exception('Missing cart_id');

        $stmt = $db->prepare("DELETE FROM shopping_cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        
        return jsonResponse(['success' => true]);
    }

    // Create order
    if ($action === 'create_order') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        // Get cart items
        $cartStmt = $db->prepare("
            SELECT sc.product_id, sc.quantity, p.price, p.discount_percent
            FROM shopping_cart sc
            JOIN products p ON sc.product_id = p.id
            WHERE sc.user_id = ?
        ");
        $cartStmt->execute([$_SESSION['user_id']]);
        $cartItems = $cartStmt->fetchAll();

        if (empty($cartItems)) throw new Exception('Cart is empty');

        // Calculate total
        $total = 0;
        foreach ($cartItems as $item) {
            $price = $item['price'] * (1 - $item['discount_percent'] / 100);
            $total += $price * $item['quantity'];
        }

        // Create order
        $order_number = 'ORD-' . date('YmdHis') . '-' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, 
                              shipping_address, total_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $order_number, $_SESSION['user_id'], $_POST['customer_name'] ?? '',
            $_POST['customer_email'] ?? '', $_POST['customer_phone'] ?? '',
            $_POST['shipping_address'] ?? '', $total
        ]);

        $order_id = $db->lastInsertId();

        // Add order items
        foreach ($cartItems as $item) {
            $itemStmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Clear cart
        $delStmt = $db->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
        $delStmt->execute([$_SESSION['user_id']]);

        return jsonResponse(['success' => true, 'order_id' => $order_id, 'order_number' => $order_number]);
    }

    throw new Exception('Invalid action');

} catch (Exception $e) {
    return errorResponse($e->getMessage(), 400);
}

function jsonResponse($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function unauthorized() {
    return errorResponse('Unauthorized', 403);
}

function sanitizeSlug($text) {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $text), '-'));
}
