<?php
/**
 * E-Commerce Products API
 * Products, Shopping Cart, and Orders management
 * Prepared for Payment Gateway Integration
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Get all products with pagination and filters
    if ($action === 'list_products') {
        $category_id = $_GET['category_id'] ?? null;
        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);
        $search = $_GET['search'] ?? null;
        
        $sql = "SELECT id, name_ar, slug, description_ar, image_url, price, discount_percent, stock_quantity, is_featured, rating_average, rating_count FROM products WHERE is_active = TRUE";
        
        if ($category_id) $sql .= " AND category_id = " . intval($category_id);
        if ($search) $sql .= " AND name_ar LIKE '%" . $db->real_escape_string($search) . "%'";
        
        $sql .= " ORDER BY is_featured DESC, created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get single product with all details
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
            SELECT r.id, r.rating, r.title_ar, r.content_ar, r.created_at, u.full_name 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.reviewable_type = 'product' AND r.reviewable_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC LIMIT 10
        ");
        $revStmt->execute([$product_id]);
        $product['reviews'] = $revStmt->fetchAll();

        return jsonResponse($product);
    }

    // Add to shopping cart
    if ($action === 'add_to_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if (!$product_id || $quantity <= 0) throw new Exception('Invalid product_id or quantity');

        // Check stock
        $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock');
        }

        $cartStmt = $db->prepare("
            INSERT INTO shopping_cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $cartStmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
        
        return jsonResponse(['success' => true, 'message' => 'Added to cart']);
    }

    // Get user shopping cart
    if ($action === 'get_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $stmt = $db->prepare("
            SELECT sc.id, sc.product_id, sc.quantity, p.name_ar, p.price, p.discount_percent, p.image_url, p.stock_quantity
            FROM shopping_cart sc
            JOIN products p ON sc.product_id = p.id
            WHERE sc.user_id = ?
            ORDER BY sc.added_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as &$item) {
            $discountedPrice = $item['price'] * (1 - $item['discount_percent'] / 100);
            $item['total'] = $discountedPrice * $item['quantity'];
            $subtotal += $item['total'];
        }
        
        return jsonResponse([
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'tax' => round($subtotal * 0.15, 2),
            'total' => round($subtotal * 1.15, 2)
        ]);
    }

    // Update cart item quantity
    if ($action === 'update_cart_item') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $cart_id = intval($_POST['cart_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if (!$cart_id || $quantity <= 0) throw new Exception('Invalid parameters');

        $stmt = $db->prepare("UPDATE shopping_cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        
        return jsonResponse(['success' => true]);
    }

    // Remove from cart
    if ($action === 'remove_from_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $cart_id = intval($_POST['cart_id'] ?? 0);
        if (!$cart_id) throw new Exception('Missing cart_id');

        $stmt = $db->prepare("DELETE FROM shopping_cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        
        return jsonResponse(['success' => true]);
    }

    // Clear entire cart
    if ($action === 'clear_cart') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $stmt = $db->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        return jsonResponse(['success' => true]);
    }

    // Create order - IMPORTANT: Called BEFORE payment
    if ($action === 'create_order') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $customer_email = sanitize($_POST['customer_email'] ?? '');
        $customer_phone = sanitize($_POST['customer_phone'] ?? '');
        $shipping_address = sanitize($_POST['shipping_address'] ?? '');
        $payment_method = sanitize($_POST['payment_method'] ?? 'credit_card');

        if (!$customer_name || !$customer_email || !$shipping_address) {
            throw new Exception('Missing required customer information');
        }

        // Get cart items
        $cartStmt = $db->prepare("
            SELECT sc.product_id, sc.quantity, p.price, p.discount_percent, p.name_ar
            FROM shopping_cart sc
            JOIN products p ON sc.product_id = p.id
            WHERE sc.user_id = ?
        ");
        $cartStmt->execute([$_SESSION['user_id']]);
        $cartItems = $cartStmt->fetchAll();

        if (empty($cartItems)) throw new Exception('Cart is empty');

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $discountedPrice = $item['price'] * (1 - $item['discount_percent'] / 100);
            $subtotal += $discountedPrice * $item['quantity'];
        }

        $tax_amount = round($subtotal * 0.15, 2);
        $shipping_cost = floatval($_POST['shipping_cost'] ?? 0);
        $total_amount = round($subtotal + $tax_amount + $shipping_cost, 2);

        // Create order
        $order_number = 'ORD-' . date('YmdHis') . '-' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO orders 
            (order_number, user_id, customer_name, customer_email, customer_phone, 
             shipping_address, billing_address, total_amount, tax_amount, shipping_cost, 
             status, payment_method, payment_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'unpaid', NOW())
        ");
        
        $stmt->execute([
            $order_number, $_SESSION['user_id'], $customer_name, $customer_email, $customer_phone,
            $shipping_address, sanitize($_POST['billing_address'] ?? $shipping_address),
            $total_amount, $tax_amount, $shipping_cost, $payment_method
        ]);

        $order_id = $db->lastInsertId();

        // Add order items
        foreach ($cartItems as $item) {
            $itemStmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name_ar, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $discountedPrice = $item['price'] * (1 - $item['discount_percent'] / 100);
            $itemShipping = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name_ar, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $itemShipping->execute([$order_id, $item['product_id'], $item['name_ar'], $item['quantity'], $item['price'], $discountedPrice * $item['quantity']]);
        }

        // Log activity
        logActivity($db, $_SESSION['user_id'], 'order_created', "إنشاء طلب: $order_number", 'orders', $order_id);

        return jsonResponse([
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'message' => 'Order created. Proceed to payment.'
        ]);
    }

    // Get user orders
    if ($action === 'get_user_orders') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);

        $stmt = $db->prepare("
            SELECT id, order_number, total_amount, status, payment_status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get order details
    if ($action === 'get_order') {
        $order_id = intval($_GET['order_id'] ?? 0);
        if (!$order_id) throw new Exception('Missing order_id');

        $stmt = $db->prepare("
            SELECT id, order_number, user_id, customer_name, customer_email, customer_phone,
                   shipping_address, total_amount, tax_amount, shipping_cost, status, 
                   payment_method, payment_status, created_at
            FROM orders
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) throw new Exception('Order not found', 404);

        // Check authorization
        if ($order['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
            throw new Exception('Unauthorized', 403);
        }

        // Get order items
        $itemsStmt = $db->prepare("
            SELECT product_id, product_name_ar, quantity, unit_price, subtotal
            FROM order_items
            WHERE order_id = ?
        ");
        $itemsStmt->execute([$order_id]);
        $order['items'] = $itemsStmt->fetchAll();

        return jsonResponse($order);
    }

    // Admin: Get all orders
    if ($action === 'get_all_orders') {
        if (!isAdmin()) return unauthorized();

        $status = $_GET['status'] ?? '';
        $payment_status = $_GET['payment_status'] ?? '';
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);

        $sql = "SELECT id, order_number, customer_name, customer_email, total_amount, status, payment_status, payment_method, created_at FROM orders WHERE 1=1";
        
        if ($status) $sql .= " AND status = '" . $db->real_escape_string($status) . "'";
        if ($payment_status) $sql .= " AND payment_status = '" . $db->real_escape_string($payment_status) . "'";
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);

        return jsonResponse($stmt->fetchAll());
    }

    // Admin: Update order status
    if ($action === 'update_order_status') {
        if (!isAdmin()) return unauthorized();

        $order_id = intval($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');

        if (!$order_id || !in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            throw new Exception('Invalid order_id or status');
        }

        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            logActivity($db, $_SESSION['admin_id'], 'order_status_updated', "تحديث حالة الطلب ID: $order_id إلى $status", 'orders', $order_id);
            $response['success'] = true;
            $response['message'] = 'Order status updated';
            return jsonResponse($response);
        }
    }

    // === PAYMENT GATEWAY INTEGRATION POINTS ===

    // Process payment - Called by payment gateway after successful payment
    if ($action === 'process_payment') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $payment_method = sanitize($_POST['payment_method'] ?? '');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        $payment_status = sanitize($_POST['payment_status'] ?? 'paid'); // paid, failed, pending

        if (!$order_id || !$transaction_id) {
            throw new Exception('Missing order_id or transaction_id');
        }

        // Update order payment status
        $stmt = $db->prepare("UPDATE orders SET payment_status = ?, payment_method = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$payment_status, $payment_method, $order_id, $_SESSION['user_id']])) {
            
            // Record transaction
            if ($payment_status === 'paid') {
                $transStmt = $db->prepare("
                    INSERT INTO payment_transactions 
                    (order_id, user_id, payment_method, transaction_id, amount, status, response_data, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                // Get order amount
                $orderStmt = $db->prepare("SELECT total_amount FROM orders WHERE id = ?");
                $orderStmt->execute([$order_id]);
                $order = $orderStmt->fetch();
                
                $transStmt->execute([
                    $order_id, 
                    $_SESSION['user_id'], 
                    $payment_method, 
                    $transaction_id, 
                    $order['total_amount'],
                    $payment_status,
                    json_encode($_POST)
                ]);

                // Update order status to processing
                $updateStmt = $db->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                $updateStmt->execute([$order_id]);

                // Clear user cart
                $cartStmt = $db->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
                $cartStmt->execute([$_SESSION['user_id']]);

                logActivity($db, $_SESSION['user_id'], 'payment_processed', "معالجة دفع للطلب ID: $order_id", 'orders', $order_id);

                return jsonResponse([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'order_id' => $order_id
                ]);
            } else {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Payment failed',
                    'order_id' => $order_id
                ]);
            }
        }
    }

    // Verify payment - Call this to check payment status
    if ($action === 'verify_payment') {
        $transaction_id = sanitize($_GET['transaction_id'] ?? '');
        if (!$transaction_id) throw new Exception('Missing transaction_id');

        $stmt = $db->prepare("SELECT status FROM payment_transactions WHERE transaction_id = ? LIMIT 1");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) throw new Exception('Transaction not found', 404);

        return jsonResponse([
            'transaction_id' => $transaction_id,
            'status' => $transaction['status']
        ]);
    }

    // Get payment config (for frontend, no sensitive data)
    if ($action === 'get_payment_config') {
        $config = [
            'apple_pay_enabled' => isDefined('APPLE_PAY_MERCHANT_ID'),
            'stripe_enabled' => isDefined('STRIPE_PUBLIC_KEY'),
            'paypal_enabled' => isDefined('PAYPAL_CLIENT_ID'),
            'fawry_enabled' => isDefined('FAWRY_MERCHANT_ID'),
            'thawani_enabled' => isDefined('THAWANI_API_KEY'),
            'currencies' => ['SAR', 'USD', 'EGP']
        ];
        return jsonResponse($config);
    }

    throw new Exception('Invalid action');

} catch (Exception $e) {
    return errorResponse($e->getMessage(), $e->getCode() ?: 400);
}

function jsonResponse($data) {
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function unauthorized() {
    return errorResponse('Unauthorized', 403);
}

function isDefined($constant) {
    return defined($constant) && !empty(constant($constant));
}

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
