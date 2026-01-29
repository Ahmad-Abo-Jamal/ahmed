<?php
/**
 * Contact Form API Endpoint
 * Endpoint: /api/contact.php
 * Method: POST
 * Required fields: name, email, subject, message
 * Optional fields: phone
 */

// Disable output buffering
if (ob_get_level()) ob_end_clean();

require_once '../config.php';

// Set headers for CORS and JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get and validate input
$name = sanitize($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'الاسم مطلوب';
}

if (!$email) {
    $errors[] = 'البريد الإلكتروني غير صحيح';
}

if (empty($subject)) {
    $errors[] = 'الموضوع مطلوب';
}

if (empty($message)) {
    $errors[] = 'الرسالة مطلوبة';
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors),
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Insert message into database
try {
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent)
        VALUES (:name, :email, :phone, :subject, :message, :ip, :user_agent)
    ");
    
    $result = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    if ($result) {
        // Success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.'
        ], JSON_UNESCAPED_UNICODE);
        
        // Optional: Send email notification to admin
        // Uncomment and configure if needed
        /*
        $admin_email = getSetting($db, 'site_email', 'admin@example.com');
        $email_subject = "رسالة جديدة من: {$name}";
        $email_body = "
            الاسم: {$name}
            البريد الإلكتروني: {$email}
            الهاتف: {$phone}
            الموضوع: {$subject}
            
            الرسالة:
            {$message}
        ";
        
        mail($admin_email, $email_subject, $email_body, "From: noreply@yourdomain.com");
        */
        
    } else {
        throw new Exception('فشل إدراج البيانات');
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم. يرجى المحاولة لاحقاً.'
    ], JSON_UNESCAPED_UNICODE);
    
    // Log error for debugging (in production, log to file instead)
    error_log('Contact Form Error: ' . $e->getMessage());
}