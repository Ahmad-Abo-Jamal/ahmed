<?php
// Tags & Categories Management

$tagGroupsStmt = $db->prepare("SELECT * FROM tag_groups WHERE is_active = TRUE ORDER BY display_order");
$tagGroupsStmt->execute();
$tagGroups = $tagGroupsStmt->fetchAll();

$tagsStmt = $db->prepare("
    SELECT t.*, tg.name_ar as group_name FROM tags t
    LEFT JOIN tag_groups tg ON t.tag_group_id = tg.id
    ORDER BY t.usage_count DESC
    LIMIT 500
");
$tagsStmt->execute();
$tags = $tagsStmt->fetchAll();
?>

<style>
.tags-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.tabs-header { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid var(--border-color); }
.tab-button { padding: 15px 25px; background: transparent; border: none; cursor: pointer; font-weight: 600; color: var(--text-dark); border-bottom: 3px solid transparent; }
.tab-button.active { border-bottom-color: var(--primary-blue); color: var(--primary-blue); }
.tab-content { display: none; }
.tab-content.active { display: block; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.btn-add { padding: 10px 20px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.tags-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
.tag-card { background: var(--bg-light); padding: 15px; border-radius: 10px; border-left: 4px solid #3498db; }
.tag-name { font-weight: 600; color: var(--primary-blue); margin-bottom: 8px; }
.tag-group { font-size: 12px; color: #666; margin-bottom: 8px; }
.tag-usage { font-size: 12px; color: #999; }
.tag-actions { display: flex; gap: 8px; margin-top: 10px; }
.btn-small { padding: 6px 12px; font-size: 12px; border: none; border-radius: 5px; cursor: pointer; }
.btn-edit-sm { background: #3498db; color: white; }
.btn-delete-sm { background: #e74c3c; color: white; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-blue); }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; font-family: 'Cairo', sans-serif; }
.add-form { background: var(--bg-light); padding: 20px; border-radius: 10px; margin-bottom: 20px; display: none; }
.btn-save { padding: 10px 20px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; }
.btn-cancel { padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 8px; cursor: pointer; }
.categories-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.categories-table th { background: var(--bg-light); padding: 15px; text-align: right; font-weight: 600; color: var(--primary-blue); border-bottom: 2px solid var(--border-color); }
.categories-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); }
</style>

<div class="tags-container">
    <div class="tabs-header">
        <button class="tab-button active" onclick="switchTab('tags')">الوسوم</button>
        <button class="tab-button" onclick="switchTab('categories')">الفئات</button>
    </div>

    <!-- TAGS TAB -->
    <div id="tags" class="tab-content active">
        <div class="section-header">
            <h2>إدارة الوسوم</h2>
            <button class="btn-add" onclick="showAddTagForm()"><i class="fas fa-plus"></i> إضافة وسم</button>
        </div>

        <div class="add-form" id="addTagForm">
            <h3>إضافة وسم جديد</h3>
            <form onsubmit="saveTag(event)">
                <div class="form-group">
                    <label>اسم الوسم (عربي) *</label>
                    <input type="text" name="name_ar" required>
                </div>
                <div class="form-group">
                    <label>مجموعة الوسم</label>
                    <select name="group_id">
                        <option value="">لا مجموعة</option>
                        <?php foreach($tagGroups as $group): ?>
                            <option value="<?php echo $group['id']; ?>"><?php echo sanitize($group['name_ar']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>اللون</label>
                    <input type="color" name="color_code" value="#3498db">
                </div>
                <button type="submit" class="btn-save">حفظ</button>
                <button type="button" class="btn-cancel" onclick="hideAddTagForm()">إلغاء</button>
            </form>
        </div>

        <!-- Tag Groups -->
        <?php foreach($tagGroups as $group): ?>
            <h4 style="margin-top: 25px; color: var(--primary-blue);"><?php echo sanitize($group['name_ar']); ?></h4>
            <div class="tags-grid">
                <?php 
                    foreach($tags as $tag) {
                        if ($tag['tag_group_id'] == $group['id']) {
                            echo '<div class="tag-card">';
                            echo '<div class="tag-name">' . sanitize($tag['name_ar']) . '</div>';
                            echo '<div class="tag-usage">الاستخدام: ' . $tag['usage_count'] . '</div>';
                            echo '<div class="tag-actions">';
                            echo '<button class="btn-small btn-edit-sm" onclick="editTag(' . $tag['id'] . ')">تعديل</button>';
                            echo '<button class="btn-small btn-delete-sm" onclick="deleteTag(' . $tag['id'] . ')">حذف</button>';
                            echo '</div></div>';
                        }
                    }
                ?>
            </div>
        <?php endforeach; ?>

        <!-- Ungrouped Tags -->
        <h4 style="margin-top: 25px; color: var(--primary-blue);">وسوم بدون مجموعة</h4>
        <div class="tags-grid">
            <?php 
                foreach($tags as $tag) {
                    if (!$tag['tag_group_id']) {
                        echo '<div class="tag-card">';
                        echo '<div class="tag-name">' . sanitize($tag['name_ar']) . '</div>';
                        echo '<div class="tag-usage">الاستخدام: ' . $tag['usage_count'] . '</div>';
                        echo '<div class="tag-actions">';
                        echo '<button class="btn-small btn-edit-sm" onclick="editTag(' . $tag['id'] . ')">تعديل</button>';
                        echo '<button class="btn-small btn-delete-sm" onclick="deleteTag(' . $tag['id'] . ')">حذف</button>';
                        echo '</div></div>';
                    }
                }
            ?>
        </div>
    </div>

    <!-- CATEGORIES TAB -->
    <div id="categories" class="tab-content">
        <div class="section-header">
            <h2>إدارة الفئات</h2>
            <button class="btn-add" onclick="alert('إضافة فئات جديدة')"><i class="fas fa-plus"></i> إضافة فئة</button>
        </div>

        <table class="categories-table">
            <thead>
                <tr>
                    <th>اسم الفئة</th>
                    <th>الوصف</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>الإلكترونيات</td>
                    <td>منتجات إلكترونية متنوعة</td>
                    <td><span style="color: green;">✓ مفعلة</span></td>
                    <td>
                        <button class="btn-small btn-edit-sm">تعديل</button>
                        <button class="btn-small btn-delete-sm">حذف</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    
    // Deactivate all buttons
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function showAddTagForm() {
    document.getElementById('addTagForm').style.display = 'block';
}

function hideAddTagForm() {
    document.getElementById('addTagForm').style.display = 'none';
}

function saveTag(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'create_tag');

    fetch('/ahmed/api/tags.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم إضافة الوسم بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + data.error);
        }
    });
}

function editTag(tagId) {
    alert('سيتم إضافة صفحة التعديل قريباً');
}

function deleteTag(tagId) {
    if (confirm('هل أنت متأكد من حذف هذا الوسم؟')) {
        alert('سيتم حذف الوسم');
    }
}
</script>
