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
                <button class="btn" onclick="document.getElementById('addUserModal').style.display='flex'">
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

    <script>
        function editUser(id) {
            alert('سيتم فتح صفحة التعديل قريباً');
        }

        function deleteUser(id, name) {
            if (confirm(`هل أنت متأكد من حذف المستخدم "${name}"?`)) {
                // Delete logic here
                alert('تم الحذف بنجاح');
            }
        }
    </script>
</body>
</html>
