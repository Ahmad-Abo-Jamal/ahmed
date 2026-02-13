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

    <script>
        function openNewCampaign() {
            alert('سيتم فتح نموذج الحملة الجديدة قريباً');
        }

        function viewCampaign(id) {
            alert('سيتم عرض تفاصيل الحملة قريباً');
        }

        function editCampaign(id) {
            alert('سيتم فتح محرر الحملة قريباً');
        }

        function sendCampaign(id) {
            if (confirm('هل تريد إرسال هذه الحملة لجميع المشتركين؟')) {
                alert('سيتم إرسال الحملة قريباً');
            }
        }

        function viewSubscribers() {
            alert('سيتم عرض قائمة المشتركين قريباً');
        }

        function exportSubscribers() {
            alert('سيتم تصدير ملف CSV قريباً');
        }

        function importSubscribers() {
            alert('سيتم فتح نموذج الاستيراد قريباً');
        }
    </script>
</body>
</html>
