<?php
// Email Templates Management

$option = $_GET['option'] ?? 'list';
$templates = [];
$templateStmt = $db->prepare("SELECT * FROM email_templates ORDER BY created_at DESC");
$templateStmt->execute();
$templates = $templateStmt->fetchAll();
?>

<style>
.email-templates-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.templates-header { display: flex; justify-content: space-between; margin-bottom: 25px; }
.templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.template-card { background: var(--bg-light); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-blue); }
.template-name { font-size: 18px; font-weight: 600; color: var(--primary-blue); margin-bottom: 10px; }
.template-subject { font-size: 14px; color: #666; margin-bottom: 15px; }
.template-actions { display: flex; gap: 10px; }
.btn-test { padding: 8px 15px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; }
.btn-edit { padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; }
.template-form { background: var(--bg-light); padding: 25px; border-radius: 10px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-blue); }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; font-family: 'Cairo', sans-serif; }
.form-group textarea { min-height: 200px; }
.btn-save { padding: 12px 25px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
</style>

<div class="email-templates-container">
    <div class="templates-header">
        <h2>إدارة قوالب البريد الإلكتروني</h2>
    </div>

    <div class="templates-grid" id="templatesGrid">
        <?php foreach ($templates as $template): ?>
            <div class="template-card">
                <div class="template-name"><?php echo sanitize($template['name_ar']); ?></div>
                <div class="template-subject">الموضوع: <?php echo sanitize($template['subject_ar']); ?></div>
                <div class="template-actions">
                    <button class="btn-test" onclick="testEmail(<?php echo $template['id']; ?>)">
                        <i class="fas fa-paper-plane"></i> اختبار
                    </button>
                    <button class="btn-edit" onclick="editTemplate(<?php echo $template['id']; ?>)">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function testEmail(templateId) {
    const email = prompt('أدخل عنوان البريد الإلكتروني للاختبار:');
    if (!email) return;

    fetch('/ahmed/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=send_test&template_id=' + templateId + '&test_email=' + email
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم إرسال البريد بنجاح');
        } else {
            alert('خطأ: ' + data.error);
        }
    });
}

function editTemplate(templateId) {
    // Redirect to edit page
    window.location.href = '?page=email_templates&edit=' + templateId;
}
</script>
