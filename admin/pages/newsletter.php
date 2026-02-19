<?php
// Get subscribers stats
$subscribersStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_confirmed = TRUE THEN 1 ELSE 0 END) as confirmed FROM newsletter_subscribers WHERE is_active = TRUE");
$subscribersStmt->execute();
$stats = $subscribersStmt->fetch();

// Get recent campaigns
$campaignsStmt = $db->prepare("SELECT id, title, subject, status, recipients_count, sent_count, open_count, sent_at FROM newsletter_campaigns ORDER BY created_at DESC LIMIT 20");
$campaignsStmt->execute();
$campaigns = $campaignsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --primary-blue: #08137b;
            --secondary-purple: #4f09a7;
            --accent-green: #2e7d32;
            --white: #ffffff;
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; direction: rtl; }
        .container { padding: 20px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .header h1 {
            color: var(--primary-blue);
            font-size: 2rem;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .section {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f5f5f5;
            padding: 12px;
            text-align: right;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-draft { background: #e0e0e0; color: #333; }
        .status-scheduled { background: #fff3cd; color: #856404; }
        .status-sent { background: #d4edda; color: #155724; }
        .status-archived { background: #f8d7da; color: #721c24; }

        .actions {
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
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-open-text"></i> إدارة النشرة البريدية</h1>
            <button class="btn" onclick="openNewCampaign()">
                <i class="fas fa-plus"></i> حملة جديدة
            </button>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label">إجمالي المشتركين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['confirmed'] ?? 0 ?></div>
                <div class="stat-label">مشتركو مؤكدون</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($campaigns) ?></div>
                <div class="stat-label">الحملات المرسلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= intval(($stats['confirmed'] ?? 0) / max(1, $stats['total'] ?? 1) * 100) ?>%</div>
                <div class="stat-label">نسبة التأكيد</div>
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-history"></i> الحملات الأخيرة
            </div>

            <?php if (count($campaigns) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>الموضوع</th>
                            <th>الحالة</th>
                            <th>المراسلون</th>
                            <th>الفتوحات</th>
                            <th>تاريخ الإرسال</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars(substr($campaign['subject'], 0, 50)) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $campaign['status'] ?>">
                                        <?php
                                        $status_labels = [
                                            'draft' => 'مسودة',
                                            'scheduled' => 'مجدولة',
                                            'sent' => 'مرسلة',
                                            'archived' => 'مؤرشفة'
                                        ];
                                        echo $status_labels[$campaign['status']] ?? $campaign['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?= $campaign['sent_count'] ?> / <?= $campaign['recipients_count'] ?></td>
                                <td><?= $campaign['open_count'] ?? 0 ?></td>
                                <td><?= $campaign['sent_at'] ? date('Y-m-d H:i', strtotime($campaign['sent_at'])) : '-' ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-small" onclick="viewCampaign(<?= $campaign['id'] ?>)">
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                        <?php if ($campaign['status'] === 'draft'): ?>
                                            <button class="btn-small" onclick="editCampaign(<?= $campaign['id'] ?>)">
                                                <i class="fas fa-edit"></i> تعديل
                                            </button>
                                            <button class="btn-small" onclick="sendCampaign(<?= $campaign['id'] ?>)">
                                                <i class="fas fa-paper-plane"></i> إرسال
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>لم تُرسل أي حملات حتى الآن</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Subscribers Management -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-users"></i> إدارة المشتركين
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="btn" onclick="document.getElementById('subscriberModal').style.display='flex'">
                    <i class="fas fa-plus"></i> مشترك جديد
                </button>
                <button class="btn" onclick="viewSubscribers()">
                    <i class="fas fa-list"></i> عرض المشتركين
                </button>
                <button class="btn" onclick="exportSubscribers()">
                    <i class="fas fa-download"></i> تصدير CSV
                </button>
                <button class="btn" onclick="importSubscribers()">
                    <i class="fas fa-upload"></i> استيراد
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.css">

    <!-- Campaign Modal -->
    <div id="campaignModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto;">
            <h2 id="campaignModalTitle">حملة جديدة</h2>
            <form id="campaignForm" style="margin-top: 20px;">
                <input type="hidden" id="campaignId" name="id" value="">
                
                <div style="margin-bottom: 15px;">
                    <label>عنوان الحملة *</label>
                    <input type="text" id="campaignTitle" name="title" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>موضوع البريد *</label>
                    <input type="text" id="campaignSubject" name="subject" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>المحتوى *</label>
                    <textarea id="campaignContent" name="content" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal; min-height: 200px;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('campaignModal').style.display='none'" style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 4px; cursor: pointer;">إلغاء</button>
                    <button type="submit" style="padding: 10px 20px; background: #08137b; color: white; border: none; border-radius: 4px; cursor: pointer;">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscriber Modal -->
    <div id="subscriberModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px;">
            <h2>إضافة مشترك جديد</h2>
            <form id="subscriberForm" style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>البريد الإلكتروني *</label>
                    <input type="email" id="subscriberEmail" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الاسم</label>
                    <input type="text" id="subscriberName" name="name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('subscriberModal').style.display='none'" style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 4px; cursor: pointer;">إلغاء</button>
                    <button type="submit" style="padding: 10px 20px; background: #08137b; color: white; border: none; border-radius: 4px; cursor: pointer;">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isEditMode = false;

        function openNewCampaign() {
            isEditMode = false;
            document.getElementById('campaignModalTitle').innerHTML = 'حملة جديدة';
            document.getElementById('campaignForm').reset();
            document.getElementById('campaignId').value = '';
            document.getElementById('campaignModal').style.display = 'flex';
        }

        function editCampaign(id) {
            isEditMode = true;
            document.getElementById('campaignModalTitle').innerHTML = 'تعديل الحملة';
            
            fetch(`/admin/ajax/newsletter.php?action=get_campaign&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('campaignId').value = data.data.id;
                        document.getElementById('campaignTitle').value = data.data.title;
                        document.getElementById('campaignSubject').value = data.data.subject;
                        document.getElementById('campaignContent').value = data.data.content;
                        document.getElementById('campaignModal').style.display = 'flex';
                    }
                });
        }

        function viewCampaign(id) {
            fetch(`/admin/ajax/newsletter.php?action=get_campaign&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const campaign = data.data;
                        Swal.fire({
                            title: campaign.title,
                            html: `<div style="text-align: right; direction: rtl;">
                                <p><strong>الموضوع:</strong> ${campaign.subject}</p>
                                <p><strong>الحالة:</strong> ${campaign.status}</p>
                                <div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px; text-align: right;">
                                    ${campaign.content}
                                </div>
                            </div>`,
                            showCloseButton: true
                        });
                    }
                });
        }

        function sendCampaign(id) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم إرسال هذه الحملة إلى جميع المشتركين',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#08137b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، أرسل الآن',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/ajax/newsletter.php?action=send_campaign', {
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

        function viewSubscribers() {
            window.location.href = 'subscribers.php';
        }

        function exportSubscribers() {
            Swal.fire({
                title: 'تصدير المشتركين',
                text: 'سيتم تحميل ملف CSV بقائمة جميع المشتركين',
                icon: 'info',
                showConfirmButton: true,
                confirmButtonColor: '#08137b'
            }).then(() => {
                fetch('/admin/ajax/newsletter.php?action=subscribers_list')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            const csv = 'البريد الإلكتروني,الاسم,التأكيد,التاريخ\n' +
                                data.data.map(s => `${s.email},${s.name},${s.is_confirmed ? 'موثق' : 'انتظار'},${s.created_at}`).join('\n');
                            const blob = new Blob([csv], {type: 'text/csv'});
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'subscribers.csv';
                            a.click();
                        }
                    });
            });
        }

        function importSubscribers() {
            Swal.fire({
                title: 'استيراد المشتركين',
                text: 'هذه الميزة ستتوفر قريباً',
                icon: 'info'
            });
        }

        document.getElementById('campaignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditMode ? 'edit_campaign' : 'add_campaign';
            formData.append('action', action);
            formData.append('status', 'draft');
            
            const params = new URLSearchParams(formData);
            
            fetch('/admin/ajax/newsletter.php', {
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

        document.getElementById('subscriberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_subscriber');
            
            const params = new URLSearchParams(formData);
            
            fetch('/admin/ajax/newsletter.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('تم!', data.message, 'success').then(() => {
                        document.getElementById('subscriberModal').style.display = 'none';
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ', data.message, 'error');
                }
            });
        });
    </script>
</body>
</html>
