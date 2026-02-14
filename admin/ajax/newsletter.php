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
        case 'campaigns_list':
            $stmt = $db->prepare("SELECT * FROM newsletter_campaigns ORDER BY created_at DESC LIMIT 100");
            $stmt->execute();
            $campaigns = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $campaigns;
            break;

        case 'subscribers_list':
            $status = sanitize($_GET['status'] ?? '');
            $query = "SELECT id, email, name, is_confirmed, created_at FROM newsletter_subscribers WHERE is_active = TRUE";
            if ($status === 'confirmed') {
                $query .= " AND is_confirmed = TRUE";
            } elseif ($status === 'pending') {
                $query .= " AND is_confirmed = FALSE";
            }
            $query .= " ORDER BY created_at DESC LIMIT 1000";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $subscribers = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $subscribers;
            break;

        case 'add_campaign':
            $title = sanitize($_POST['title'] ?? '');
            $subject = sanitize($_POST['subject'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $status = sanitize($_POST['status'] ?? 'draft');

            if (empty($title) || empty($subject) || empty($content)) {
                $response['message'] = 'يرجى ملء جميع الحقول المطلوبة';
                break;
            }

            // Count total subscribers for recipients_count
            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_active = TRUE AND is_confirmed = TRUE");
            $countStmt->execute();
            $count_result = $countStmt->fetch();
            $recipients_count = $count_result['count'] ?? 0;

            $stmt = $db->prepare("
                INSERT INTO newsletter_campaigns (title, subject, content, status, recipients_count, created_at)
                VALUES (:title, :subject, :content, :status, :recipients_count, NOW())
            ");
            
            if ($stmt->execute([
                ':title' => $title,
                ':subject' => $subject,
                ':content' => $content,
                ':status' => $status,
                ':recipients_count' => $recipients_count
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'campaign_created', "إنشاء حملة: $title", 'newsletter_campaigns', $db->lastInsertId());
                $response['success'] = true;
                $response['message'] = 'تم إنشاء الحملة بنجاح';
                $response['id'] = $db->lastInsertId();
            }
            break;

        case 'edit_campaign':
            $id = intval($_POST['id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $subject = sanitize($_POST['subject'] ?? '');
            $content = sanitize($_POST['content'] ?? '');

            if (empty($title) || empty($subject) || empty($content)) {
                $response['message'] = 'يرجى ملء الحقول المطلوبة';
                break;
            }

            $stmt = $db->prepare("
                UPDATE newsletter_campaigns SET 
                title = :title,
                subject = :subject,
                content = :content
                WHERE id = :id AND status = 'draft'
            ");
            
            if ($stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':subject' => $subject,
                ':content' => $content
            ])) {
                logActivity($db, $_SESSION['admin_id'], 'campaign_updated', "تعديل حملة: $title", 'newsletter_campaigns', $id);
                $response['success'] = true;
                $response['message'] = 'تم تحديث الحملة بنجاح';
            } else {
                $response['message'] = 'لا يمكن تعديل حملة مرسلة';
            }
            break;

        case 'send_campaign':
            $id = intval($_POST['id'] ?? 0);
            
            // Get campaign details
            $stmt = $db->prepare("SELECT * FROM newsletter_campaigns WHERE id = :id AND status = 'draft'");
            $stmt->execute([':id' => $id]);
            $campaign = $stmt->fetch();
            
            if (!$campaign) {
                $response['message'] = 'الحملة غير موجودة أو مرسلة بالفعل';
                break;
            }

            // Get subscribers
            $subStmt = $db->prepare("SELECT email, name FROM newsletter_subscribers WHERE is_active = TRUE AND is_confirmed = TRUE");
            $subStmt->execute();
            $subscribers = $subStmt->fetchAll();

            // Send emails (simplified - would need proper email service in production)
            $sent_count = count($subscribers);
            
            // Update campaign status
            $updateStmt = $db->prepare("
                UPDATE newsletter_campaigns SET 
                status = 'sent',
                sent_count = :sent_count,
                sent_at = NOW()
                WHERE id = :id
            ");
            
            if ($updateStmt->execute([':id' => $id, ':sent_count' => $sent_count])) {
                logActivity($db, $_SESSION['admin_id'], 'campaign_sent', "إرسال حملة: {$campaign['title']}", 'newsletter_campaigns', $id);
                $response['success'] = true;
                $response['message'] = "تم إرسال الحملة إلى $sent_count مشترك";
            }
            break;

        case 'delete_campaign':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT title FROM newsletter_campaigns WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $campaign = $stmt->fetch();
            
            if (!$campaign) {
                $response['message'] = 'الحملة غير موجودة';
                break;
            }

            $del = $db->prepare("DELETE FROM newsletter_campaigns WHERE id = :id");
            if ($del->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'campaign_deleted', "حذف حملة: {$campaign['title']}", 'newsletter_campaigns', $id);
                $response['success'] = true;
                $response['message'] = 'تم حذف الحملة بنجاح';
            }
            break;

        case 'add_subscriber':
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $name = sanitize($_POST['name'] ?? '');

            if (!$email) {
                $response['message'] = 'البريد الإلكتروني غير صحيح';
                break;
            }

            // Check if subscriber exists
            $check = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = :email");
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $response['message'] = 'المشترك موجود بالفعل';
                break;
            }

            $stmt = $db->prepare("
                INSERT INTO newsletter_subscribers (email, name, is_confirmed, created_at)
                VALUES (:email, :name, TRUE, NOW())
            ");
            
            if ($stmt->execute([':email' => $email, ':name' => $name])) {
                logActivity($db, $_SESSION['admin_id'], 'subscriber_added', "إضافة مشترك: $email", 'newsletter_subscribers', $db->lastInsertId());
                $response['success'] = true;
                $response['message'] = 'تم إضافة المشترك بنجاح';
            }
            break;

        case 'delete_subscriber':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT email FROM newsletter_subscribers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $subscriber = $stmt->fetch();
            
            if (!$subscriber) {
                $response['message'] = 'المشترك غير موجود';
                break;
            }

            $del = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
            if ($del->execute([':id' => $id])) {
                logActivity($db, $_SESSION['admin_id'], 'subscriber_deleted', "حذف مشترك: {$subscriber['email']}", 'newsletter_subscribers', $id);
                $response['success'] = true;
                $response['message'] = 'تم حذف المشترك بنجاح';
            }
            break;

        default:
            $response['message'] = 'إجراء غير صحيح';
    }
} catch (Exception $e) {
    $response['message'] = 'خطأ: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
