<style>
.messages-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.messages-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.filter-tabs {
    display: flex;
    gap: 10px;
}

.filter-tab {
    padding: 10px 20px;
    border: 2px solid var(--border-color);
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.filter-tab.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.messages-table {
    width: 100%;
    border-collapse: collapse;
}

.messages-table th {
    background: var(--bg-light);
    padding: 15px;
    text-align: right;
    font-weight: 600;
    color: var(--primary-blue);
    border-bottom: 2px solid var(--border-color);
}

.messages-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.messages-table tr:hover {
    background: var(--bg-light);
}

.status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-new { background: #3498db; color: white; }
.status-read { background: #95a5a6; color: white; }
.status-replied { background: #27ae60; color: white; }
.status-archived { background: #7f8c8d; color: white; }

.action-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    margin: 0 3px;
}

.btn-view {
    background: #3498db;
    color: white;
}

.btn-reply {
    background: #27ae60;
    color: white;
}

.btn-delete {
    background: #e74c3c;
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 50px auto;
    padding: 30px;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
}

.modal-header h2 {
    color: var(--primary-blue);
}

.close-btn {
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.close-btn:hover {
    color: #333;
}

.message-detail {
    margin-bottom: 15px;
}

.message-detail label {
    display: block;
    font-weight: 600;
    color: var(--primary-blue);
    margin-bottom: 5px;
}

.message-detail p {
    padding: 10px;
    background: var(--bg-light);
    border-radius: 8px;
}

textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-family: 'Cairo', sans-serif;
    resize: vertical;
    min-height: 150px;
}

.btn-submit {
    background: var(--primary-blue);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-family: 'Cairo', sans-serif;
    font-size: 16px;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    background: var(--secondary-purple);
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .messages-table {
        font-size: 14px;
    }
    
    .messages-table th,
    .messages-table td {
        padding: 10px 5px;
    }
    
    .filter-tabs {
        width: 100%;
        overflow-x: auto;
    }
}
</style>

<?php
// Get messages
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM contact_messages";
if ($filter !== 'all') {
    $sql .= " WHERE status = :status";
}
$sql .= " ORDER BY created_at DESC LIMIT 50";

try {
    $stmt = $db->prepare($sql);
    if ($filter !== 'all') {
        $stmt->execute([':status' => $filter]);
    } else {
        $stmt->execute();
    }
    $messages = $stmt->fetchAll();
} catch(PDOException $e) {
    $messages = [];
}
?>

<div class="messages-container">
    <div class="messages-header">
        <div class="filter-tabs">
            <button class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                    onclick="location.href='?page=messages&filter=all'">
                الكل
            </button>
            <button class="filter-tab <?php echo $filter === 'new' ? 'active' : ''; ?>" 
                    onclick="location.href='?page=messages&filter=new'">
                جديدة
            </button>
            <button class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>" 
                    onclick="location.href='?page=messages&filter=read'">
                مقروءة
            </button>
            <button class="filter-tab <?php echo $filter === 'replied' ? 'active' : ''; ?>" 
                    onclick="location.href='?page=messages&filter=replied'">
                تم الرد
            </button>
        </div>
    </div>
    
    <?php if (count($messages) > 0): ?>
        <table class="messages-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الموضوع</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td><?php echo sanitize($msg['name']); ?></td>
                        <td><?php echo sanitize($msg['email']); ?></td>
                        <td><?php echo sanitize(mb_substr($msg['subject'], 0, 50)); ?>...</td>
                        <td>
                            <span class="status-badge status-<?php echo $msg['status']; ?>">
                                <?php 
                                $statuses = ['new' => 'جديدة', 'read' => 'مقروءة', 'replied' => 'تم الرد', 'archived' => 'مؤرشفة'];
                                echo $statuses[$msg['status']] ?? $msg['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($msg['created_at'])); ?></td>
                        <td>
                            <button class="action-btn btn-view" onclick="viewMessage(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn btn-reply" onclick="replyMessage(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-reply"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>لا توجد رسائل</h3>
            <p>لم يتم استلام أي رسائل بعد</p>
        </div>
    <?php endif; ?>
</div>

<!-- View Message Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>تفاصيل الرسالة</h2>
            <span class="close-btn" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div id="messageDetails"></div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>الرد على الرسالة</h2>
            <span class="close-btn" onclick="closeModal('replyModal')">&times;</span>
        </div>
        <div id="replyForm"></div>
    </div>
</div>

<script>
function viewMessage(id) {
    $.get('/admin/ajax/messages.php?action=view&id=' + id, function(response) {
        if (response.success) {
            const msg = response.data;
            $('#messageDetails').html(`
                <div class="message-detail">
                    <label>الاسم:</label>
                    <p>${msg.name}</p>
                </div>
                <div class="message-detail">
                    <label>البريد الإلكتروني:</label>
                    <p>${msg.email}</p>
                </div>
                <div class="message-detail">
                    <label>الهاتف:</label>
                    <p>${msg.phone || 'غير متوفر'}</p>
                </div>
                <div class="message-detail">
                    <label>الموضوع:</label>
                    <p>${msg.subject}</p>
                </div>
                <div class="message-detail">
                    <label>الرسالة:</label>
                    <p>${msg.message}</p>
                </div>
                <div class="message-detail">
                    <label>التاريخ:</label>
                    <p>${msg.created_at}</p>
                </div>
            `);
            $('#viewModal').show();
        }
    });
}

function replyMessage(id) {
    $.get('/admin/ajax/messages.php?action=view&id=' + id, function(response) {
        if (response.success) {
            const msg = response.data;
            $('#replyForm').html(`
                <div class="message-detail">
                    <label>إلى: ${msg.email}</label>
                </div>
                <div class="message-detail">
                    <label>الرد:</label>
                    <textarea id="replyText" placeholder="اكتب ردك هنا..."></textarea>
                </div>
                <button class="btn-submit" onclick="sendReply(${id})">
                    <i class="fas fa-paper-plane"></i> إرسال الرد
                </button>
            `);
            $('#replyModal').show();
        }
    });
}

function sendReply(id) {
    const reply = $('#replyText').val();
    if (!reply) {
        Swal.fire('خطأ', 'يرجى كتابة الرد', 'error');
        return;
    }
    
    $.post('/admin/ajax/messages.php', {
        action: 'reply',
        id: id,
        reply: reply
    }, function(response) {
        if (response.success) {
            Swal.fire('نجح', 'تم إرسال الرد بنجاح', 'success');
            closeModal('replyModal');
            location.reload();
        } else {
            Swal.fire('خطأ', response.message, 'error');
        }
    });
}

function deleteMessage(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'لن تتمكن من استرجاع هذه الرسالة!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('/admin/ajax/messages.php', {
                action: 'delete',
                id: id
            }, function(response) {
                if (response.success) {
                    Swal.fire('تم الحذف', 'تم حذف الرسالة بنجاح', 'success');
                    location.reload();
                }
            });
        }
    });
}

function closeModal(modalId) {
    $('#' + modalId).hide();
}

// Close modal when clicking outside (on the dark background)
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        $(this).hide();
    }
});
</script>