<?php
// Reviews & Ratings Management

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';

// Build query based on filters
$whereConditions = [];
if ($statusFilter !== 'all') {
    $whereConditions[] = "r.status = '$statusFilter'";
}
if ($typeFilter !== 'all') {
    $whereConditions[] = "r.reviewable_type = '$typeFilter'";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$reviewsStmt = $db->prepare("
    SELECT r.id, r.title_ar, r.content_ar, r.rating, r.status, r.is_verified_purchase, r.created_at,
           u.full_name, r.reviewable_type, r.reviewable_id, r.user_id
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT 500
");
$reviewsStmt->execute();
$reviews = $reviewsStmt->fetchAll();

// Get statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reviews
");
$statsStmt->execute();
$stats = $statsStmt->fetch();

// Get content titles for reviews
function getContentTitle($db, $type, $id) {
    if ($type === 'article') {
        $stmt = $db->prepare("SELECT title FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['title'] : "مقالة رقم $id";
    } elseif ($type === 'dictionary') {
        $stmt = $db->prepare("SELECT word_ar FROM dictionary WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['word_ar'] : "كلمة رقم $id";
    } elseif ($type === 'service') {
        $stmt = $db->prepare("SELECT title FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['title'] : "خدمة رقم $id";
    } elseif ($type === 'product') {
        $stmt = $db->prepare("SELECT title FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['title'] : "منتج رقم $id";
    }
    return "محتوى رقم $id";
}
?>

<style>
.reviews-container { 
    background: white; 
    padding: 30px; 
    border-radius: 15px; 
    box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
}

.reviews-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 25px; 
}

.reviews-filters {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-dark);
}

.filter-group select {
    padding: 8px 12px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.95rem;
    cursor: pointer;
}

.filter-tabs { 
    display: flex; 
    gap: 15px; 
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-tab { 
    padding: 10px 20px; 
    background: transparent; 
    border: 2px solid var(--border-color); 
    cursor: pointer; 
    border-radius: 8px; 
    font-weight: 600; 
    color: var(--text-dark); 
    transition: all 0.3s;
}

.filter-tab:hover {
    border-color: var(--primary-blue);
}

.filter-tab.active { 
    background: var(--primary-blue); 
    color: white; 
    border-color: var(--primary-blue); 
}

.reviews-grid { 
    display: grid; 
    gap: 20px; 
}

.review-card { 
    background: var(--bg-light); 
    padding: 20px; 
    border-radius: 10px; 
    border-left: 4px solid #f39c12;
    transition: all 0.3s;
}

.review-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.review-card.status-pending {
    border-left-color: #f39c12;
}

.review-card.status-approved {
    border-left-color: #27ae60;
}

.review-card.status-rejected {
    border-left-color: #e74c3c;
}

.review-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: start; 
    margin-bottom: 15px; 
}

.reviewer-info { 
    flex: 1; 
}

.reviewer-name { 
    font-weight: 600; 
    color: var(--primary-blue); 
}

.review-meta { 
    font-size: 12px; 
    color: #666; 
    margin-top: 5px; 
}

.rating-stars { 
    color: #f39c12; 
    margin-right: 10px; 
}

.verified-badge { 
    display: inline-block; 
    padding: 3px 8px; 
    background: #27ae60; 
    color: white; 
    border-radius: 4px; 
    font-size: 11px; 
}

.content-badge {
    display: inline-block;
    padding: 5px 10px;
    background: var(--primary-blue);
    color: white;
    border-radius: 4px;
    font-size: 11px;
    margin-left: 10px;
}

.review-title { 
    font-weight: 600; 
    margin: 15px 0 10px 0; 
    color: var(--primary-blue); 
}

.review-content { 
    color: #555; 
    line-height: 1.6; 
    margin-bottom: 15px;
    max-height: 100px;
    overflow-y: auto;
}

.review-metadata {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 15px;
    padding: 10px;
    background: white;
    border-radius: 5px;
}

.review-actions { 
    display: flex; 
    gap: 10px;
    flex-wrap: wrap;
}

.btn-approve { 
    padding: 8px 15px; 
    background: #27ae60; 
    color: white; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer;
    transition: all 0.3s;
}

.btn-approve:hover {
    background: #229954;
}

.btn-reject { 
    padding: 8px 15px; 
    background: #e74c3c; 
    color: white; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer;
    transition: all 0.3s;
}

.btn-reject:hover {
    background: #c0392b;
}

.btn-delete {
    padding: 8px 15px;
    background: #95a5a6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-delete:hover {
    background: #7f8c8d;
}

.status-badge { 
    padding: 5px 12px; 
    border-radius: 15px; 
    font-size: 11px; 
    font-weight: 600; 
}

.status-pending { 
    background: #f39c12; 
    color: white; 
}

.status-approved { 
    background: #27ae60; 
    color: white; 
}

.status-rejected { 
    background: #e74c3c; 
    color: white; 
}

.stats-bar { 
    display: flex; 
    gap: 20px; 
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.stat { 
    display: flex; 
    flex-direction: column;
    padding: 15px 20px;
    background: var(--bg-light);
    border-radius: 10px;
    border-left: 4px solid var(--primary-blue);
}

.stat-number { 
    font-size: 24px; 
    font-weight: 700; 
    color: var(--primary-blue); 
}

.stat-label { 
    font-size: 12px; 
    color: #666; 
    margin-top: 5px; 
}

.reject-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.reject-modal-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
}

.reject-modal-content h3 {
    margin-bottom: 15px;
    color: var(--primary-blue);
}

.reject-modal-content textarea {
    width: 100%;
    min-height: 100px;
    padding: 10px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    font-family: inherit;
    margin-bottom: 15px;
}

.reject-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<div class="reviews-container">
    <div class="reviews-header">
        <h2>إدارة التقييمات والمراجعات</h2>
    </div>

    <!-- Statistics -->
    <div class="stats-bar">
        <div class="stat">
            <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-label">إجمالي المراجعات</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">قيد المراجعة</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
            <div class="stat-label">معتمدة</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></div>
            <div class="stat-label">مرفوضة</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="reviews-filters">
        <div class="filter-group">
            <label>نوع المحتوى:</label>
            <select onchange="location.href='?page=reviews&type=' + this.value + '&status=<?php echo $statusFilter; ?>'">
                <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>الكل</option>
                <option value="article" <?php echo $typeFilter === 'article' ? 'selected' : ''; ?>>مقالات</option>
                <option value="dictionary" <?php echo $typeFilter === 'dictionary' ? 'selected' : ''; ?>>قاموس</option>
                <option value="service" <?php echo $typeFilter === 'service' ? 'selected' : ''; ?>>خدمات</option>
                <option value="product" <?php echo $typeFilter === 'product' ? 'selected' : ''; ?>>منتجات</option>
            </select>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" onclick="location.href='?page=reviews&status=all&type=<?php echo $typeFilter; ?>'">الكل</button>
        <button class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" onclick="location.href='?page=reviews&status=pending&type=<?php echo $typeFilter; ?>'">قيد المراجعة</button>
        <button class="filter-tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>" onclick="location.href='?page=reviews&status=approved&type=<?php echo $typeFilter; ?>'">معتمدة</button>
        <button class="filter-tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" onclick="location.href='?page=reviews&status=rejected&type=<?php echo $typeFilter; ?>'">مرفوضة</button>
    </div>

    <!-- Reviews Grid -->
    <div class="reviews-grid" id="reviewsGrid">
        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <p>لا توجد مراجعات مطابقة للفلترة المختارة</p>
            </div>
        <?php else: ?>
            <?php foreach($reviews as $review): ?>
                <div class="review-card status-<?php echo $review['status']; ?>">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-name">
                                <?php echo $review['full_name'] ?: 'مستخدم مجهول'; ?>
                            </div>
                            <div class="review-meta">
                                <span class="rating-stars">
                                    <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </span>
                                <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?>
                                <?php if($review['is_verified_purchase']): ?>
                                    <span class="verified-badge">✓ موثق</span>
                                <?php endif; ?>
                                <span class="content-badge">
                                    <?php 
                                        $typeLabels = [
                                            'article' => 'مقالة',
                                            'dictionary' => 'قاموس',
                                            'service' => 'خدمة',
                                            'product' => 'منتج'
                                        ];
                                        echo $typeLabels[$review['reviewable_type']] ?? $review['reviewable_type'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $review['status']; ?>">
                            <?php 
                                if ($review['status'] === 'pending') echo 'قيد المراجعة';
                                elseif ($review['status'] === 'approved') echo 'معتمدة';
                                else echo 'مرفوضة';
                            ?>
                        </span>
                    </div>
                    
                    <div class="review-title"><?php echo sanitize($review['title_ar']); ?></div>
                    
                    <div class="review-metadata">
                        <strong>المحتوى:</strong> <?php echo getContentTitle($db, $review['reviewable_type'], $review['reviewable_id']); ?><br>
                        <strong>النوع:</strong> <?php echo $review['reviewable_type']; ?> | 
                        <strong>المعرّف:</strong> <?php echo $review['reviewable_id']; ?>
                    </div>
                    
                    <?php if($review['content_ar']): ?>
                        <div class="review-content"><?php echo sanitize($review['content_ar']); ?></div>
                    <?php endif; ?>

                    <?php if($review['status'] === 'pending'): ?>
                        <div class="review-actions">
                            <button class="btn-approve" onclick="approveReview(<?php echo $review['id']; ?>)">
                                <i class="fas fa-check"></i> الموافقة
                            </button>
                            <button class="btn-reject" onclick="openRejectModal(<?php echo $review['id']; ?>)">
                                <i class="fas fa-times"></i> الرفض
                            </button>
                        </div>
                    <?php elseif ($review['status'] === 'approved'): ?>
                        <div class="review-actions">
                            <button class="btn-reject" onclick="rejectApprovedReview(<?php echo $review['id']; ?>)">
                                <i class="fas fa-times"></i> إلغاء الموافقة
                            </button>
                            <button class="btn-delete" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="reject-modal">
    <div class="reject-modal-content">
        <h3>رفض المراجعة</h3>
        <p>أضف ملاحظات الرفض (اختياري):</p>
        <textarea id="rejectNotes" placeholder="سبب الرفض..."></textarea>
        <div class="reject-modal-actions">
            <button onclick="closeRejectModal()" class="btn-reject" style="background: #95a5a6;">إلغاء</button>
            <button onclick="submitReject()" class="btn-reject">تأكيد الرفض</button>
        </div>
    </div>
</div>

<script>
let currentRejectId = null;

function openRejectModal(reviewId) {
    currentRejectId = reviewId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectNotes').value = '';
}

function submitReject() {
    const notes = document.getElementById('rejectNotes').value;
    rejectReview(currentRejectId, notes);
}

function approveReview(reviewId) {
    if (confirm('تأكيد الموافقة على هذه المراجعة؟')) {
        fetch('/admin/ajax/reviews.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=approve&id=' + reviewId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('تم الموافقة على المراجعة');
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        });
    }
}

function rejectReview(reviewId, notes) {
    fetch('/admin/ajax/reviews.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=reject&id=' + reviewId + '&admin_notes=' + encodeURIComponent(notes)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم رفض المراجعة');
            location.reload();
        } else {
            alert('خطأ: ' + data.message);
        }
    });
    closeRejectModal();
}

function rejectApprovedReview(reviewId) {
    if (confirm('هل أنت متأكد من رغبتك في إلغاء موافقة هذه المراجعة؟')) {
        rejectReview(reviewId, 'تم إلغاء الموافقة');
    }
}

function deleteReview(reviewId) {
    if (confirm('هل أنت متأكد من حذف هذه المراجعة؟ هذا الإجراء لا يمكن التراجع عنه')) {
        fetch('/admin/ajax/reviews.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete&id=' + reviewId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('تم حذف المراجعة');
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        });
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('rejectModal');
    if (e.target === modal) {
        closeRejectModal();
    }
});
</script>

```
