<style>
.articles-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.articles-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.btn-add { padding: 12px 25px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.article-card { background: var(--bg-light); padding: 20px; border-radius: 10px; transition: transform 0.3s; }
.article-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.article-title { font-size: 18px; font-weight: 600; color: var(--primary-blue); margin-bottom: 10px; }
.article-category { display: inline-block; padding: 5px 12px; background: var(--accent-gold); color: white; border-radius: 15px; font-size: 12px; margin-bottom: 10px; }
.article-actions { display: flex; gap: 10px; margin-top: 15px; }
.btn-edit, .btn-delete { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-edit { background: #3498db; color: white; }
.btn-delete { background: #e74c3c; color: white; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-blue); }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; font-family: 'Cairo', sans-serif; }
</style>

<?php
$stmt = $db->prepare("SELECT a.*, u.full_name as author FROM articles a JOIN admin_users u ON a.author_id = u.id ORDER BY a.created_at DESC");
$stmt->execute();
$articles = $stmt->fetchAll();
?>

<div class="articles-container">
    <div class="articles-header">
        <h2>إدارة المقالات</h2>
        <button class="btn-add" onclick="showAddForm()"><i class="fas fa-plus"></i> إضافة مقال</button>
    </div>
    
    <div class="articles-grid" id="articlesGrid">
        <?php foreach ($articles as $article): ?>
            <div class="article-card">
                <div class="article-category"><?php echo $article['category']; ?></div>
                <div class="article-title"><?php echo sanitize($article['title']); ?></div>
                <p><?php echo sanitize(mb_substr($article['excerpt'], 0, 100)); ?>...</p>
                <div style="color: #999; font-size: 14px; margin-top: 10px;">
                    <i class="fas fa-user"></i> <?php echo $article['author']; ?> |
                    <i class="fas fa-eye"></i> <?php echo $article['views']; ?> مشاهدة
                </div>
                <div class="article-actions">
                    <button class="btn-edit" onclick="editArticle(<?php echo $article['id']; ?>)">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                    <button class="btn-delete" onclick="deleteArticle(<?php echo $article['id']; ?>)">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="articleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">إضافة مقال جديد</h2>
            <span class="close-btn" onclick="closeModal('articleModal')">&times;</span>
        </div>
        <form id="articleForm">
            <input type="hidden" id="articleId" name="id">
            <div class="form-group">
                <label>العنوان</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label>الرابط (slug)</label>
                <input type="text" id="slug" name="slug" required>
            </div>
            <div class="form-group">
                <label>المقتطف</label>
                <textarea id="excerpt" name="excerpt" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>المحتوى</label>
                <textarea id="content" name="content" rows="6" required></textarea>
                <div style="margin-top:8px; display:flex; gap:8px; align-items:center">
                    <button type="button" id="previewBtn" class="btn-edit">معاينة</button>
                    <div id="readingInfo" style="color:#666; font-size:14px; margin-left:10px">الكلمات: <span id="wordCount">0</span> • وقت القراءة: <strong id="readingTime">0</strong> دقيقة</div>
                </div>
            </div>
            <div class="form-group">
                <label>التصنيف</label>
                <select id="category" name="category">
                    <option value="article">مقالات</option>
                    <option value="book">كتب</option>
                    <option value="course">دورات</option>
                    <option value="service">خدمات</option>
                    <option value="news">أخبار</option>
                </select>
            </div>
            <div class="form-group">
                <label>الوسم (Badge)</label>
                <input type="text" id="badge" name="badge" placeholder="جديد، قريباً، إطلاق">
            </div>
            <div class="form-group">
                <label>رابط الصورة</label>
                <input type="url" id="image_url" name="image_url">
                <div style="margin-top:8px; display:flex; gap:8px; align-items:center">
                    <input type="file" id="imageFile" accept="image/*">
                    <button type="button" id="uploadImageBtn" class="btn-edit">رفع الصورة</button>
                    <img id="imagePreview" src="" alt="preview" style="height:40px;display:none;border-radius:6px;margin-left:8px">
                </div>
            </div>
            <div class="form-group">
                <label>الحالة</label>
                <select id="status" name="status">
                    <option value="draft">مسودة</option>
                    <option value="published">منشور</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">حفظ</button>
        </form>
    </div>
</div>

<script>
// Load TinyMCE for a modern editor
let tinyMceReady = false;
const tmceScript = document.createElement('script');
tmceScript.src = 'https://cdn.jsdelivr.net/npm/tinymce@6.8.0/tinymce.min.js';
tmceScript.referrerPolicy = 'origin';
document.head.appendChild(tmceScript);
tmceScript.onload = () => {
    tinymce.init({ 
        selector: '#content',
        height: 300,
        menubar: false,
        plugins: 'link image code lists media table paste imagetools',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | code',
        automatic_uploads: true,
        images_upload_url: '/admin/ajax/upload_image.php',
        images_upload_handler: function (blobInfo, success, failure) {
            var xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open('POST', '/admin/ajax/upload_image.php');
            xhr.onload = function() {
                if (xhr.status !== 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }
                var json = JSON.parse(xhr.responseText);
                if (!json || (!json.location && !json.url)) {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                success(json.location || json.url);
            };
            var formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        },
        init_instance_callback: function() {
            tinyMceReady = true;
        }
    });
};

function getTinyContent() {
    return (typeof tinymce !== 'undefined' && tinymce.get('content')) ? tinymce.get('content').getContent() : $('#content').val();
}

function setTinyContent(content) {
    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
        tinymce.get('content').setContent(content);
    } else {
        $('#content').val(content);
    }
}

function showAddForm() {
    $('#modalTitle').text('إضافة مقال جديد');
    $('#articleForm')[0].reset();
    $('#articleId').val('');
    setTinyContent('');
    $('#articleModal').show();
}

function editArticle(id) {
    $.get('/admin/ajax/articles.php?action=get&id=' + id, function(response) {
        if (response.success) {
            const article = response.data;
            $('#modalTitle').text('تعديل المقال');
            $('#articleId').val(article.id);
            $('#title').val(article.title);
            $('#slug').val(article.slug);
            $('#excerpt').val(article.excerpt);
            setTinyContent(article.content);
            $('#category').val(article.category);
            $('#badge').val(article.badge);
            $('#image_url').val(article.image_url);
            $('#status').val(article.status);
            $('#articleModal').show();
        }
    });
}

$('#articleForm').on('submit', function(e) {
    e.preventDefault();
    // ensure we get content from TinyMCE if available
    $('#content').val(getTinyContent());
    const formData = $(this).serialize();
    const action = $('#articleId').val() ? 'update' : 'create';
    
    $.post('/admin/ajax/articles.php', formData + '&action=' + action, function(response) {
        if (response.success) {
            Swal.fire('نجح', response.message, 'success');
            $('#articleModal').hide();
            refreshArticles();
        } else {
            Swal.fire('خطأ', response.message, 'error');
        }
    });
});

function refreshArticles(){
    $.get('/admin/ajax/articles.php?action=list', function(response){
        if (!response.success) return;
        const grid = $('#articlesGrid'); grid.empty();
        response.data.forEach(article => {
            const card = $(`\
                <div class="article-card">\
                    <div class="article-category">${article.category}</div>\
                    <div class="article-title">${article.title}</div>\
                    <p>${(article.excerpt||'').substring(0,100)}...</p>\
                    <div style="color: #999; font-size: 14px; margin-top: 10px;">\
                        <i class="fas fa-user"></i> ${article.author} |\
                        <i class="fas fa-eye"></i> ${article.reading_time || '—'} دقيقة تقديرية\
                    </div>\
                    <div class="article-actions">\
                        <button class="btn-edit" onclick="editArticle(${article.id})"><i class="fas fa-edit"></i> تعديل</button>\
                        <button class="btn-delete" onclick="deleteArticle(${article.id})"><i class="fas fa-trash"></i> حذف</button>\
                    </div>\
                </div>
            `);
            grid.append(card);
        });
    }, 'json');
}

function deleteArticle(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف المقال نهائياً!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('/admin/ajax/articles.php', { action: 'delete', id: id }, function(response) {
                if (response.success) {
                    Swal.fire('تم', 'تم حذف المقال', 'success');
                    refreshArticles();
                }
            });
        }
    });
}

// Auto-generate slug from title
$('#title').on('input', function() {
    if (!$('#articleId').val()) {
        const slug = $(this).val().toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '');
        $('#slug').val(slug);
    }
});

// Close modal when clicking outside (on the dark background)
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        $(this).hide();
    }
});

// Preview button
document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'previewBtn'){
        const content = getTinyContent();
        const win = window.open('', '_blank');
        win.document.write('<html><head><meta charset="utf-8"><title>معاينة</title></head><body>'+content+'</body></html>');
    }
});

// Update reading stats live
function updateReadingStats(){
    const content = getTinyContent();
    const words = content.trim() ? content.trim().split(/\s+/).length : 0;
    const minutes = Math.max(1, Math.ceil(words / 200));
    document.getElementById('wordCount').textContent = words;
    document.getElementById('readingTime').textContent = minutes;
}

setInterval(updateReadingStats, 1000);
</script>
