<?php
require_once '../../config.php';
requireAdmin();
header('Content-Type: application/json');

try {
    if (empty($_FILES['image'])) throw new Exception('No file uploaded');

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error');

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) throw new Exception('Invalid file type');

    $uploadDir = __DIR__ . '/../../uploads/articles';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Move failed');

    $url = '/uploads/articles/' . $name;
    echo json_encode(['success' => true, 'url' => $url]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
