<?php
// Get influencers
$influencersStmt = $db->prepare("SELECT * FROM influencers ORDER BY is_featured DESC, follower_count DESC LIMIT 500");
$influencersStmt->execute();
$influencers = $influencersStmt->fetchAll();

// Get pending contacts
$contactsStmt = $db->prepare("SELECT COUNT(*) as count FROM influencer_contacts WHERE response_status = 'pending'");
$contactsStmt->execute();
$pending_contacts = $contactsStmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --primary-blue: #08137b;
            --secondary-purple: #4f09a7;
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

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple));
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            background: #ffd700;
            color: #333;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .influencers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .influencer-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
        }

        .influencer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2rem;
        }

        .card-body {
            padding: 20px;
        }

        .influencer-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 8px;
        }

        .influencer-category {
            color: var(--secondary-purple);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat {
            text-align: center;
            font-size: 0.85rem;
        }

        .stat-value {
            font-weight: 700;
            color: var(--primary-blue);
            display: block;
        }

        .stat-label {
            color: #999;
            font-size: 0.75rem;
        }

        .platform {
            display: inline-block;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 15px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            flex: 1;
            padding: 8px;
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

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        .featured-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffd700;
            color: #333;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
        }

        .verification-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> إدارة المؤثرين</h1>
            <div class="header-actions">
                <button class="btn" onclick="goToContacts()">
                    <i class="fas fa-envelope"></i> طلبات التواصل
                    <?php if ($pending_contacts > 0): ?>
                        <span class="badge" style="margin-right: 10px; background: #ff6b6b; color: white;"><?= $pending_contacts ?></span>
                    <?php endif; ?>
                </button>
                <button class="btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> مؤثر جديد
                </button>
            </div>
        </div>

        <?php if (count($influencers) > 0): ?>
            <div class="influencers-grid">
                <?php foreach ($influencers as $inf): ?>
                    <div class="influencer-card">
                        <div class="card-header">
                            <?php if ($inf['is_featured']): ?>
                                <div class="featured-indicator"><i class="fas fa-star"></i></div>
                            <?php endif; ?>
                            <?php if ($inf['verification_status'] !== 'unverified'): ?>
                                <div class="verification-badge">
                                    <?= $inf['verification_status'] === 'verified' ? '✓ موثق' : '👑 بريميوم' ?>
                                </div>
                            <?php endif; ?>
                            <i class="fas fa-user-circle"></i>
                        </div>

                        <div class="card-body">
                            <div class="influencer-name"><?= htmlspecialchars($inf['name_ar']) ?></div>
                            <div class="influencer-category"><?= htmlspecialchars($inf['category_ar'] ?? $inf['category']) ?></div>
                            
                            <div class="stats">
                                <div class="stat">
                                    <span class="stat-value"><?= number_format($inf['follower_count']) ?></span>
                                    <span class="stat-label">المتابعون</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?= number_format($inf['engagement_rate'], 1) ?>%</span>
                                    <span class="stat-label">التفاعل</span>
                                </div>
                            </div>

                            <div class="platform">
                                <i class="fab fa-<?= $inf['platform'] === 'instagram' ? 'instagram' : ($inf['platform'] === 'youtube' ? 'youtube' : 'tiktok') ?>"></i>
                                <?= ucfirst($inf['platform']) ?>
                            </div>

                            <div class="actions">
                                <button class="btn-small" onclick="editInfluencer(<?= $inf['id'] ?>)">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                                <a href="<?= htmlspecialchars($inf['platform_url']) ?>" target="_blank" class="btn-small" style="text-decoration: none;">
                                    <i class="fas fa-external-link-alt"></i> متابعة
                                </a>
                                <button class="btn-small" onclick="deleteInfluencer(<?= $inf['id'] ?>, '<?= htmlspecialchars($inf['name_ar']) ?>')">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>لا توجد مؤثرين حتى الآن<br><small>أضف مؤثرين لتعزيز التعاونات</small></p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.0/sweetalert2.min.css">

    <!-- Add/Edit Influencer Modal -->
    <div id="influencerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <h2 id="infModalTitle">مؤثر جديد</h2>
            <form id="influencerForm" style="margin-top: 20px;">
                <input type="hidden" id="influencerId" name="id" value="">
                
                <div style="margin-bottom: 15px;">
                    <label>الاسم بالعربية *</label>
                    <input type="text" id="nameAr" name="name_ar" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الاسم بالإنجليزية</label>
                    <input type="text" id="nameEn" name="name_en" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>الفئة</label>
                    <input type="text" id="category" name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>المنصة *</label>
                    <select id="platform" name="platform" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                        <option value="">اختر المنصة</option>
                        <option value="instagram">Instagram</option>
                        <option value="youtube">YouTube</option>
                        <option value="tiktok">TikTok</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>رابط المنصة *</label>
                    <input type="url" id="platformUrl" name="platform_url" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>عدد المتابعين</label>
                    <input type="number" id="followerCount" name="follower_count" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>معدل التفاعل (%)</label>
                    <input type="number" id="engagementRate" name="engagement_rate" step="0.1" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" id="isFeatured" name="is_featured"> مؤثر مميز
                    </label>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>حالة التحقق</label>
                    <select id="verificationStatus" name="verification_status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Tajawal;">
                        <option value="unverified">غير موثق</option>
                        <option value="verified">موثق</option>
                        <option value="premium">بريميوم</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('influencerModal').style.display='none'" style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 4px; cursor: pointer;">إلغاء</button>
                    <button type="submit" style="padding: 10px 20px; background: #08137b; color: white; border: none; border-radius: 4px; cursor: pointer;">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isEditMode = false;

        function openAddModal() {
            isEditMode = false;
            document.getElementById('infModalTitle').innerHTML = 'مؤثر جديد';
            document.getElementById('influencerForm').reset();
            document.getElementById('influencerId').value = '';
            document.getElementById('influencerModal').style.display = 'flex';
        }

        function editInfluencer(id) {
            isEditMode = true;
            document.getElementById('infModalTitle').innerHTML = 'تعديل المؤثر';
            
            fetch(`/admin/ajax/influencers.php?action=get&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('influencerId').value = data.data.id;
                        document.getElementById('nameAr').value = data.data.name_ar;
                        document.getElementById('nameEn').value = data.data.name_en || '';
                        document.getElementById('category').value = data.data.category || '';
                        document.getElementById('platform').value = data.data.platform;
                        document.getElementById('platformUrl').value = data.data.platform_url;
                        document.getElementById('followerCount').value = data.data.follower_count || 0;
                        document.getElementById('engagementRate').value = data.data.engagement_rate || 0;
                        document.getElementById('isFeatured').checked = data.data.is_featured === 1;
                        document.getElementById('verificationStatus').value = data.data.verification_status;
                        document.getElementById('influencerModal').style.display = 'flex';
                    }
                });
        }

        function deleteInfluencer(id, name) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: `سيتم حذف المؤثر "${name}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#08137b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، احذفه',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/ajax/influencers.php?action=delete', {
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

        function goToContacts() {
            window.location.href = 'messages.php?type=influencer_contact';
        }

        document.getElementById('influencerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditMode ? 'edit' : 'add';
            formData.append('action', action);
            
            const params = new URLSearchParams(formData);
            
                    fetch('/admin/ajax/influencers.php', {
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
