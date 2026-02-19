<style>
.settings-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.settings-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); }
.tab-btn { padding: 15px 25px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; color: #666; transition: all 0.3s; }
.tab-btn.active { color: var(--primary-blue); border-bottom-color: var(--primary-blue); }
.tab-content { display: none; }
.tab-content.active { display: block; }
.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
</style>

<?php
$stmt = $db->prepare("SELECT * FROM site_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="settings-container">
    <h2>الإعدادات</h2>
    
    <div class="settings-tabs">
        <button class="tab-btn active" onclick="switchTab('general')">عام</button>
        <button class="tab-btn" onclick="switchTab('social')">وسائل التواصل</button>
        <button class="tab-btn" onclick="switchTab('email')">البريد الإلكتروني</button>
        <button class="tab-btn" onclick="switchTab('password')">تغيير كلمة المرور</button>
    </div>
    
    <div id="general" class="tab-content active">
        <form id="generalForm">
            <div class="settings-grid">
                <div class="form-group">
                    <label>اسم الموقع</label>
                    <input type="text" name="site_name" value="<?php echo $settings['site_name'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="site_email" value="<?php echo $settings['site_email'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="site_phone" value="<?php echo $settings['site_phone'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>رقم الواتساب</label>
                    <input type="text" name="whatsapp_number" value="<?php echo $settings['whatsapp_number'] ?? ''; ?>">
                </div>
            </div>
            <div class="form-group">
                <label>وصف الموقع</label>
                <textarea name="site_description" rows="3"><?php echo $settings['site_description'] ?? ''; ?></textarea>
            </div>
            <button type="submit" class="btn-submit">حفظ التغييرات</button>
        </form>
    </div>
    
    <div id="social" class="tab-content">
        <form id="socialForm">
            <div class="settings-grid">
                <div class="form-group">
                    <label>Facebook</label>
                    <input type="url" name="facebook_url" value="<?php echo $settings['facebook_url'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Instagram</label>
                    <input type="url" name="instagram_url" value="<?php echo $settings['instagram_url'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>LinkedIn</label>
                    <input type="url" name="linkedin_url" value="<?php echo $settings['linkedin_url'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>YouTube</label>
                    <input type="url" name="youtube_url" value="<?php echo $settings['youtube_url'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Twitter</label>
                    <input type="url" name="twitter_url" value="<?php echo $settings['twitter_url'] ?? ''; ?>">
                </div>
            </div>
            <button type="submit" class="btn-submit">حفظ التغييرات</button>
        </form>
    </div>
    
    <div id="email" class="tab-content">
        <form id="emailForm">
            <div class="settings-grid">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo $settings['smtp_host'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?php echo $settings['smtp_port'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input type="text" name="smtp_username" value="<?php echo $settings['smtp_username'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_password" value="<?php echo $settings['smtp_password'] ?? ''; ?>">
                </div>
            </div>
            <button type="submit" class="btn-submit">حفظ التغييرات</button>
        </form>
    </div>
    
    <div id="password" class="tab-content">
        <form id="passwordForm">
            <div class="form-group">
                <label>كلمة المرور الحالية</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-submit">تغيير كلمة المرور</button>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    $('.tab-content').removeClass('active');
    $('.tab-btn').removeClass('active');
    $('#' + tabName).addClass('active');
    $('button[onclick="switchTab(\'' + tabName + '\')"]').addClass('active');
}

$('#generalForm, #socialForm, #emailForm').on('submit', function(e) {
    e.preventDefault();
    $.post('ajax/settings.php', $(this).serialize() + '&action=save', function(response) {
        if (response.success) {
            Swal.fire('نجح', 'تم حفظ الإعدادات بنجاح', 'success');
        } else {
            Swal.fire('خطأ', response.message, 'error');
        }
    });
});

$('#passwordForm').on('submit', function(e) {
    e.preventDefault();
    const newPass = $('input[name="new_password"]').val();
    const confirmPass = $('input[name="confirm_password"]').val();
    
    if (newPass !== confirmPass) {
        Swal.fire('خطأ', 'كلمتا المرور غير متطابقتين', 'error');
        return;
    }
    
    $.post('ajax/settings.php', $(this).serialize() + '&action=change_password', function(response) {
        if (response.success) {
            Swal.fire('نجح', 'تم تغيير كلمة المرور بنجاح', 'success');
            $('#passwordForm')[0].reset();
        } else {
            Swal.fire('خطأ', response.message, 'error');
        }
    });
});
</script>
