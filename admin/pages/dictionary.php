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

    <!-- Add/Edit Word Modal -->
    <div id="wordModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); overflow-y:auto;">
        <div style="background-color:var(--white); margin:5% auto; padding:30px; border-radius:var(--border-radius); width:90%; max-width:500px; box-shadow:var(--shadow-md);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="wordModalTitle">إضافة كلمة جديدة</h2>
                <span onclick="closeWordModal()" style="cursor:pointer; font-size:28px; color:#aaa;">&times;</span>
            </div>
            <form id="wordForm">
                <input type="hidden" id="wordId">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">الكلمة بالعربية *</label>
                    <input type="text" id="wordText" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">النطق</label>
                    <input type="text" id="wordPronunciation" placeholder="مثال: tah-jwal" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">التعريف *</label>
                    <textarea id="wordDefinition" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;" rows="3" placeholder="اكتب تعريف الكلمة بالتفصيل"></textarea>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">أمثلة (كل حر على سطر)</label>
                    <textarea id="wordExamples" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;" rows="2" placeholder="مثال 1&#10;مثال 2"></textarea>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">المرادفات (مفصولة بفواصل)</label>
                    <input type="text" id="wordSynonyms" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;" placeholder="كلمة1، كلمة2، كلمة3">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">الأضداد (مفصولة بفواصل)</label>
                    <input type="text" id="wordAntonyms" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;" placeholder="ضد1، ضد2">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600;">نوع الكلمة</label>
                        <select id="wordType" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;">
                            <option value="noun">اسم</option>
                            <option value="verb">فعل</option>
                            <option value="adjective">صفة</option>
                            <option value="adverb">ظرف</option>
                            <option value="preposition">حرف جر</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600;">الفئة</label>
                        <input type="text" id="wordCategory" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;" placeholder="أعمال، تقنية، عام...">
                    </div>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">مستوى الصعوبة</label>
                    <select id="wordDifficulty" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;">
                        <option value="beginner">مبتدئ</option>
                        <option value="intermediate">متوسط</option>
                        <option value="advanced">متقدم</option>
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" id="wordFeatured">
                        <span style="font-weight:600;">كلمة مميزة</span>
                    </label>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:25px; padding-top:20px; border-top:1px solid #ddd;">
                    <button type="button" onclick="closeWordModal()" style="padding:10px 20px; background:#f0f0f0; border:none; border-radius:8px; cursor:pointer; font-family:inherit;">إلغاء</button>
                    <button type="submit" style="padding:10px 20px; background:linear-gradient(135deg, var(--primary-blue), var(--secondary-purple)); color:var(--white); border:none; border-radius:8px; cursor:pointer; font-family:inherit;">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('wordId').value = '';
            document.getElementById('wordForm').reset();
            document.getElementById('wordModalTitle').textContent = 'إضافة كلمة جديدة';
            document.getElementById('wordModal').style.display = 'block';
        }

        function editWord(id) {
            fetch(`/admin/ajax/dictionary.php?action=get_word&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const word = data.word;
                        document.getElementById('wordId').value = word.id;
                        document.getElementById('wordText').value = word.word_ar || '';
                        document.getElementById('wordPronunciation').value = word.pronunciation || '';
                        document.getElementById('wordDefinition').value = word.definition_ar || '';
                        document.getElementById('wordCategory').value = word.category || '';
                        document.getElementById('wordDifficulty').value = word.difficulty_level || 'beginner';
                        document.getElementById('wordType').value = word.word_type || 'noun';
                        document.getElementById('wordFeatured').checked = word.is_featured == 1;
                        
                        // Handle examples - could be array or JSON string
                        let examples = '';
                        if (word.examples) {
                            try {
                                const exArray = Array.isArray(word.examples) ? word.examples : JSON.parse(word.examples);
                                examples = exArray.join('\n');
                            } catch(e) {
                                examples = word.examples;
                            }
                        }
                        document.getElementById('wordExamples').value = examples;
                        document.getElementById('wordSynonyms').value = word.synonyms || '';
                        document.getElementById('wordAntonyms').value = word.antonyms || '';
                        
                        document.getElementById('wordModalTitle').textContent = 'تعديل الكلمة';
                        document.getElementById('wordModal').style.display = 'block';
                    } else {
                        alert('خطأ: ' + (data.message || 'فشل تحميل البيانات'));
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert('خطأ في تحميل البيانات');
                });
        }

        function deleteWord(id, word) {
            if (confirm(`هل تريد حذف الكلمة "${word}"?`)) {
                fetch('/admin/ajax/dictionary.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete_word', id: id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.message);
                    }
                })
                .catch(e => alert('خطأ في الحذف'));
            }
        }

        function closeWordModal() {
            document.getElementById('wordModal').style.display = 'none';
        }

        document.getElementById('wordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('wordId').value;
            const action = id ? 'edit_word' : 'add_word';
            
            const data = new URLSearchParams({
                action: action,
                id: id,
                word_ar: document.getElementById('wordText').value,
                pronunciation: document.getElementById('wordPronunciation').value,
                definition_ar: document.getElementById('wordDefinition').value,
                examples: document.getElementById('wordExamples').value,
                synonyms: document.getElementById('wordSynonyms').value,
                antonyms: document.getElementById('wordAntonyms').value,
                word_type: document.getElementById('wordType').value,
                category: document.getElementById('wordCategory').value,
                difficulty_level: document.getElementById('wordDifficulty').value,
                is_featured: document.getElementById('wordFeatured').checked ? 1 : 0
            });

            fetch('/admin/ajax/dictionary.php', {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'تم الحفظ بنجاح');
                    location.reload();
                } else {
                    alert('خطأ: ' + (data.message || 'فشل الحفظ'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('خطأ في الحفظ');
            });
        });

        window.onclick = function(event) {
            const modal = document.getElementById('wordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
