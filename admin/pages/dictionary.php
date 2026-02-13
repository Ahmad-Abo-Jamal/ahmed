<?php
// Get dictionary words
$wordsStmt = $db->prepare("SELECT id, word_ar, category, difficulty_level, usage_count, is_featured, created_at FROM dictionary ORDER BY created_at DESC LIMIT 500");
$wordsStmt->execute();
$words = $wordsStmt->fetchAll();

// Get categories
$categoriesStmt = $db->prepare("SELECT DISTINCT category FROM dictionary WHERE category IS NOT NULL ORDER BY category");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
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
            --neutral-light: #f5f5f0;
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
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

        .words-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .word-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
        }

        .word-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .word-title {
            font-size: 1.5rem;
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .word-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 5px;
            margin-bottom: 10px;
        }

        .category-badge { background: #e3f2fd; color: #0d47a1; }
        .featured-badge { background: #fff3e0; color: #e65100; }

        .difficulty-beginner { background: #d4edda; color: #155724; }
        .difficulty-intermediate { background: #fff3cd; color: #856404; }
        .difficulty-advanced { background: #f8d7da; color: #721c24; }

        .actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book"></i> إدارة القاموس العربي</h1>
            <button class="btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> كلمة جديدة
            </button>
        </div>

        <?php if (count($words) > 0): ?>
            <div class="words-grid">
                <?php foreach ($words as $word): ?>
                    <div class="word-card">
                        <div class="word-title"><?= htmlspecialchars($word['word_ar']) ?></div>
                        
                        <div class="word-meta">
                            <div><strong>الفئة:</strong> <?= htmlspecialchars($word['category'] ?? 'عام') ?></div>
                            <div><strong>الاستخدامات:</strong> <?= $word['usage_count'] ?></div>
                        </div>

                        <div>
                            <span class="badge difficulty-<?= $word['difficulty_level'] ?>">
                                <?= $word['difficulty_level'] === 'beginner' ? 'مبتدئ' : ($word['difficulty_level'] === 'intermediate' ? 'متوسط' : 'متقدم') ?>
                            </span>
                            <?php if ($word['is_featured']): ?>
                                <span class="badge featured-badge"><i class="fas fa-star"></i> مميز</span>
                            <?php endif; ?>
                        </div>

                        <div class="actions">
                            <button class="btn-small" onclick="editWord(<?= $word['id'] ?>)">
                                <i class="fas fa-edit"></i> تعديل
                            </button>
                            <button class="btn-small" onclick="deleteWord(<?= $word['id'] ?>, '<?= htmlspecialchars($word['word_ar']) ?>')">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>لا توجد كلمات في القاموس<br><small>ابدأ بإضافة الكلمات الأساسية</small></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function openAddModal() {
            alert('سيتم فتح نموذج إضافة كلمة جديدة قريباً');
        }

        function editWord(id) {
            alert('سيتم فتح صفحة التعديل قريباً');
        }

        function deleteWord(id, word) {
            if (confirm(`هل تريد حذف الكلمة "${word}"?`)) {
                alert('تم الحذف بنجاح');
            }
        }
    </script>
</body>
</html>
