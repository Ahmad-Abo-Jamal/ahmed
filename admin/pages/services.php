<style>
.services-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.services-header { display: flex; justify-content: space-between; margin-bottom: 25px; }
.services-table { width: 100%; border-collapse: collapse; }
.services-table th { background: var(--bg-light); padding: 15px; text-align: right; color: var(--primary-blue); font-weight: 600; }
.services-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
.service-icon { font-size: 24px; color: var(--primary-blue); }
.status-active { color: #27ae60; font-weight: 600; }
.status-inactive { color: #e74c3c; font-weight: 600; }
</style>

<?php
$stmt = $db->prepare("SELECT * FROM services ORDER BY display_order ASC");
$stmt->execute();
$services = $stmt->fetchAll();
?>

<div class="services-container">
    <div class="services-header">
        <h2>إدارة الخدمات</h2>
        <button class="btn-add" onclick="showAddForm()"><i class="fas fa-plus"></i> إضافة خدمة</button>
    </div>
    
    <table class="services-table">
        <thead>
            <tr>
                <th>الأيقونة</th>
                <th>العنوان</th>
                <th>الوصف</th>
                <th>الترتيب</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><i class="service-icon fas <?php echo $service['icon']; ?>"></i></td>
                    <td><?php echo sanitize($service['title']); ?></td>
                    <td><?php echo sanitize(mb_substr($service['description'], 0, 80)); ?>...</td>
                    <td><?php echo $service['display_order']; ?></td>
                    <td><span class="status-<?php echo $service['status']; ?>"><?php echo $service['status'] === 'active' ? 'نشط' : 'غير نشط'; ?></span></td>
                    <td>
                        <button class="btn-edit" onclick="editService(<?php echo $service['id']; ?>)"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" onclick="deleteService(<?php echo $service['id']; ?>)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="serviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">إضافة خدمة جديدة</h2>
            <span class="close-btn" onclick="closeModal('serviceModal')">&times;</span>
        </div>
        <form id="serviceForm">
            <input type="hidden" id="serviceId" name="id">
            <div class="form-group">
                <label>العنوان</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label>الوصف</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>أيقونة Font Awesome (مثل: fa-star)</label>
                <input type="text" id="icon" name="icon" value="fa-star">
            </div>
            <div class="form-group">
                <label>ترتيب العرض</label>
                <input type="number" id="display_order" name="display_order" value="0">
            </div>
            <div class="form-group">
                <label>الحالة</label>
                <select id="status" name="status">
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">حفظ</button>
        </form>
    </div>
</div>

<script>
function showAddForm() {
    $('#modalTitle').text('إضافة خدمة جديدة');
    $('#serviceForm')[0].reset();
    $('#serviceId').val('');
    $('#serviceModal').show();
}

function editService(id) {
    $.get('/admin/ajax/services.php?action=get&id=' + id, function(response) {
        if (response.success) {
            const service = response.data;
            $('#modalTitle').text('تعديل الخدمة');
            $('#serviceId').val(service.id);
            $('#title').val(service.title);
            $('#description').val(service.description);
            $('#icon').val(service.icon);
            $('#display_order').val(service.display_order);
            $('#status').val(service.status);
            $('#serviceModal').show();
        }
    });
}

$('#serviceForm').on('submit', function(e) {
    e.preventDefault();
    const formData = $(this).serialize();
    const action = $('#serviceId').val() ? 'update' : 'create';
    
    $.post('/admin/ajax/services.php', formData + '&action=' + action, function(response) {
        if (response.success) {
            Swal.fire('نجح', response.message, 'success');
            location.reload();
        } else {
            Swal.fire('خطأ', response.message, 'error');
        }
    });
});

function deleteService(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف الخدمة نهائياً!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('/admin/ajax/services.php', { action: 'delete', id: id }, function(response) {
                if (response.success) {
                    Swal.fire('تم', 'تم حذف الخدمة', 'success');
                    location.reload();
                }
            });
        }
    });
}
// Close modal when clicking outside (on the dark background)
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        $(this).hide();
    }
});</script>
