<?php
/**
 * Email Notifications API
 * POST: send test email, update settings
 * GET: get templates, notification logs
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Get email templates
    if ($action === 'get_templates') {
        $stmt = $db->prepare("SELECT id, name, name_ar, subject_ar FROM email_templates WHERE is_active = TRUE");
        $stmt->execute();
        return jsonResponse($stmt->fetchAll());
    }

    // Send test email
    if ($action === 'send_test') {
        if (!isAdmin()) return unauthorized();
        
        $template_id = $_POST['template_id'] ?? null;
        $test_email = $_POST['test_email'] ?? null;
        
        if (!$template_id || !$test_email) {
            throw new Exception('Missing required fields');
        }

        $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();
        
        if (!$template) throw new Exception('Template not found');

        // Simple email send (integrate with PHPMailer later)
        $subject = $template['subject_ar'];
        $body = $template['content'];
        $headers = "From: noreply@aboelmajdhub.website\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        if (mail($test_email, $subject, $body, $headers)) {
            return jsonResponse(['success' => true, 'message' => 'Email sent successfully']);
        } else {
            throw new Exception('Failed to send email');
        }
    }

    // Get user notification settings
    if ($action === 'get_settings') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $stmt = $db->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return jsonResponse($stmt->fetchAll());
    }

    // Update notification settings
    if ($action === 'update_settings') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $notification_type = $_POST['notification_type'] ?? null;
        $email_enabled = $_POST['email_enabled'] ?? false;
        
        if (!$notification_type) throw new Exception('Missing notification_type');

        $stmt = $db->prepare("
            INSERT INTO notification_settings (user_id, notification_type, email_enabled) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE email_enabled = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $notification_type, $email_enabled, $email_enabled]);
        
        return jsonResponse(['success' => true, 'message' => 'Settings updated']);
    }

    // Queue a notification
    if ($action === 'queue_notification') {
        if (!isAdmin()) return unauthorized();

        $user_id = $_POST['user_id'] ?? null;
        $template_id = $_POST['template_id'] ?? null;
        $recipient_email = $_POST['recipient_email'] ?? null;
        $variables = $_POST['variables'] ?? '{}';

        $stmt = $db->prepare("
            INSERT INTO notification_queue (user_id, email_template_id, recipient_email, variables)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $template_id, $recipient_email, json_encode($variables)]);
        
        return jsonResponse(['success' => true, 'message' => 'Notification queued', 'id' => $db->lastInsertId()]);
    }

    // Get notification logs
    if ($action === 'get_logs') {
        if (!isAdmin()) return unauthorized();

        $limit = $_GET['limit'] ?? 100;
        $stmt = $db->prepare("
            SELECT nq.*, et.name_ar 
            FROM notification_queue nq
            LEFT JOIN email_templates et ON nq.email_template_id = et.id
            ORDER BY nq.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return jsonResponse($stmt->fetchAll());
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
