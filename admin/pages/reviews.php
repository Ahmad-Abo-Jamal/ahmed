<?php
// Reviews & Ratings Management

$reviewsStmt = $db->prepare("
    SELECT r.id, r.title_ar, r.rating, r.status, r.is_verified_purchase, r.created_at,
           u.full_name, r.reviewable_type, r.reviewable_id
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 500
");
$reviewsStmt->execute();
$reviews = $reviewsStmt->fetchAll();

$pendingCount = 0;
$approvedCount = 0;
foreach($reviews as $r) {
    if ($r['status'] === 'pending') $pendingCount++;
    elseif ($r['status'] === 'approved') $approvedCount++;
}
?>

<style>
.reviews-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.reviews-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.filter-tabs { display: flex; gap: 15px; margin-bottom: 20px; }
.filter-tab { padding: 10px 20px; background: transparent; border: 2px solid var(--border-color); cursor: pointer; border-radius: 8px; font-weight: 600; color: var(--text-dark); }
.filter-tab.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }
.reviews-grid { display: grid; gap: 20px; }
.review-card { background: var(--bg-light); padding: 20px; border-radius: 10px; border-left: 4px solid #f39c12; }
.review-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
.reviewer-info { flex: 1; }
.reviewer-name { font-weight: 600; color: var(--primary-blue); }
.review-meta { font-size: 12px; color: #666; margin-top: 5px; }
.rating-stars { color: #f39c12; margin-right: 10px; }
.verified-badge { display: inline-block; padding: 3px 8px; background: #27ae60; color: white; border-radius: 4px; font-size: 11px; }
.review-title { font-weight: 600; margin: 15px 0 10px 0; color: var(--primary-blue); }
.review-content { color: #555; line-height: 1.6; margin-bottom: 15px; }
.review-actions { display: flex; gap: 10px; }
.btn-approve { padding: 8px 15px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; }
.btn-reject { padding: 8px 15px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; }
.status-badge { padding: 5px 12px; border-radius: 15px; font-size: 11px; font-weight: 600; }
.status-pending { background: #f39c12; color: white; }
.status-approved { background: #27ae60; color: white; }
.status-rejected { background: #e74c3c; color: white; }
.stats-bar { display: flex; gap: 20px; margin-bottom: 20px; }
.stat { display: flex; flex-direction: column; }
.stat-number { font-size: 24px; font-weight: 700; color: var(--primary-blue); }
.stat-label { font-size: 12px; color: #666; margin-top: 5px; }
</style>

<div class="reviews-container">
    <div class="reviews-header">
        <h2>إدارة التقييمات والمراجعات</h2>
    </div>

    <!-- Statistics -->
    <div class="stats-bar">
        <div class="stat">
            <div class="stat-number"><?php echo count($reviews); ?></div>
            <div class="stat-label">إجمالي المراجعات</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $pendingCount; ?></div>
            <div class="stat-label">قيد المراجعة</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $approvedCount; ?></div>
            <div class="stat-label">معتمدة</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab active" onclick="filterReviews('all')">الكل</button>
        <button class="filter-tab" onclick="filterReviews('pending')">قيد المراجعة</button>
        <button class="filter-tab" onclick="filterReviews('approved')">معتمدة</button>
        <button class="filter-tab" onclick="filterReviews('high_rating')">5 نجوم</button>
        <button class="filter-tab" onclick="filterReviews('low_rating')">1-2 نجوم</button>
    </div>

    <!-- Reviews Grid -->
    <div class="reviews-grid" id="reviewsGrid">
        <?php foreach($reviews as $review): ?>
            <div class="review-card" data-status="<?php echo $review['status']; ?>" data-rating="<?php echo $review['rating']; ?>">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-name"><?php echo $review['full_name'] ?: 'مستخدم مجهول'; ?></div>
                        <div class="review-meta">
                            <span class="rating-stars">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </span>
                            <?php echo $review['created_at']; ?>
                            <?php if($review['is_verified_purchase']): ?>
                                <span class="verified-badge">✓ شراء موثق</span>
                            <?php endif; ?>
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
                <div class="review-content">
                    نوع: <strong><?php echo $review['reviewable_type']; ?></strong> | 
                    رقم المنتج/الخدمة: <strong><?php echo $review['reviewable_id']; ?></strong>
                </div>

                <?php if($review['status'] === 'pending'): ?>
                    <div class="review-actions">
                        <button class="btn-approve" onclick="approveReview(<?php echo $review['id']; ?>)">
                            <i class="fas fa-check"></i> الموافقة
                        </button>
                        <button class="btn-reject" onclick="rejectReview(<?php echo $review['id']; ?>)">
                            <i class="fas fa-times"></i> الرفض
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterReviews(filter) {
    const cards = document.querySelectorAll('.review-card');
    
    cards.forEach(card => {
        let show = true;
        
        if (filter === 'pending') show = card.dataset.status === 'pending';
        else if (filter === 'approved') show = card.dataset.status === 'approved';
        else if (filter === 'high_rating') show = parseInt(card.dataset.rating) === 5;
        else if (filter === 'low_rating') show = parseInt(card.dataset.rating) <= 2;
        
        card.style.display = show ? 'block' : 'none';
    });
    
    // Update tab styling
    document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
}

function approveReview(reviewId) {
    fetch('/ahmed/api/reviews.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=approve_review&review_id=' + reviewId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم الموافقة على المراجعة');
            location.reload();
        }
    });
}

function rejectReview(reviewId) {
    alert('سيتم إضافة خاصية الرفض قريباً');
}
</script>
