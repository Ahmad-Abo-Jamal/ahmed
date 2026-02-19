<?php
// Get all users
$usersStmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 500");
$usersStmt->execute();
$users = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #08137b;
            --secondary-purple: #4f09a7;
            --white: #ffffff;
            --neutral-light: #f5f5f0;
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; background: var(--neutral-light); direction: rtl; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .page-header {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--primary-blue);
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple));
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
        }

        .users-table {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--primary-blue);
            color: var(--white);
            padding: 15px;
            text-align: right;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 10px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-small:hover {
            background: var(--secondary-purple);
            color: var(--white);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> إدارة المستخدمين</h1>
            <div>
                <a href="index.php" style="color: var(--primary-blue); text-decoration: none; margin-left: 20px;">← العودة</a>
                <button class="btn" onclick="openAddUserModal()">
                    <i class="fas fa-plus"></i> مستخدم جديد
                </button>
            </div>
        </div>

        <div class="users-table">
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> الاسم</th>
                            <th><i class="fas fa-envelope"></i> البريد الإلكتروني</th>
                            <th><i class="fas fa-phone"></i> الهاتف</th>
                            <th><i class="fas fa-map-marker"></i> الدولة</th>
                            <th><i class="fas fa-check-circle"></i> تحقق البريد</th>
                            <th><i class="fas fa-bell"></i> النشرة</th>
                            <th><i class="fas fa-circle"></i> الحالة</th>
                            <th><i class="fas fa-cog"></i> الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['country'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?= $user['email_verified'] ? '#d4edda' : '#f8d7da' ?>; color: <?= $user['email_verified'] ? '#155724' : '#721c24' ?>">
                                        <?= $user['email_verified'] ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?= $user['newsletter_subscribed'] ? '#d4edda' : '#f8d7da' ?>; color: <?= $user['newsletter_subscribed'] ? '#155724' : '#721c24' ?>">
                                        <?= $user['newsletter_subscribed'] ? 'مشترك' : 'غير مشترك' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($user['status']) ?>">
                                        <?= $user['status'] === 'active' ? 'نشط' : ($user['status'] === 'inactive' ? 'غير نشط' : 'محظور') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small" onclick="editUser(<?= $user['id'] ?>)" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-small" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <p>لا توجد مستخدمين حتى الآن</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
            <h2 id="modalTitle">مستخدم جديد</h2>
            <form id="userForm" style="margin-top: 20px;">
                <input type="hidden" id="userId" name="id" value="">
                
                <div style="margin-bottom: 15px;">
                    <label>الاسم الكامل *</label>
                    <input type="text" id="fullName" name="full_name" placeholder="أدخل الاسم الكامل" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>البريد الإلكتروني *</label>
                    <input type="email" id="userEmail" name="email" placeholder="example@domain.com" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الهاتف</label>
                    <input type="tel" id="userPhone" name="phone" placeholder="رقم الهاتف" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الدولة</label>
                    <input type="text" id="userCountry" name="country" placeholder="الدولة" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div id="passwordDiv" style="margin-bottom: 15px;">
                    <label>كلمة المرور *</label>
                    <input type="password" id="userPassword" name="password" placeholder="كلمة المرور" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الحالة</label>
                    <select name="status" id="userStatus" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                        <option value="banned">محظور</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('userModal').style.display='none'" style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 4px; cursor: pointer;">إلغاء</button>
                    <button type="submit" style="padding: 10px 20px; background: #08137b; color: white; border: none; border-radius: 4px; cursor: pointer;">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.css">

    <script>
        let isEditMode = false;

        function openAddUserModal() {
            isEditMode = false;
            document.getElementById('modalTitle').innerHTML = 'مستخدم جديد';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            document.getElementById('passwordDiv').style.display = 'block';
            document.getElementById('userModal').style.display = 'flex';
        }

        function editUser(id) {
            isEditMode = true;
            document.getElementById('modalTitle').innerHTML = 'تعديل المستخدم';
            document.getElementById('userPassword').required = false;
            document.getElementById('passwordDiv').style.display = 'none';
            
            fetch(`/admin/ajax/users.php?action=get&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userId').value = data.data.id;
                        document.getElementById('fullName').value = data.data.full_name;
                        document.getElementById('userEmail').value = data.data.email;
                        document.getElementById('userPhone').value = data.data.phone || '';
                        document.getElementById('userCountry').value = data.data.country || '';
                        document.getElementById('userStatus').value = data.data.status;
                        document.getElementById('userModal').style.display = 'flex';
                    }
                });
        }

        function deleteUser(id, name) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: `سيتم حذف المستخدم "${name}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#08137b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، احذفه',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/ajax/users.php?action=delete', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `id=${id}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('تم!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ', data.message, 'error');
                        }
                    });
                }
            });
        }

        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditMode ? 'edit' : 'add';
            formData.append('action', action);
            
            const params = new URLSearchParams(formData);
            
            fetch('/admin/ajax/users.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('تم!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('خطأ', data.message, 'error');
                }
            });
        });
    </script>
</body>
</html>
