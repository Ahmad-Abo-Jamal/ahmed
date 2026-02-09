<?php
require_once 'config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /articles.html');
    exit;
}

$stmt = $db->prepare("SELECT a.*, u.full_name as author FROM articles a JOIN admin_users u ON a.author_id = u.id WHERE a.slug = ? AND a.status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$article = $stmt->fetch();
if (!$article) {
    http_response_code(404);
    echo 'مقال غير موجود';
    exit;
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($article['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body{font-family:Tahoma, Arial; background:#f5f5f0;color:#222}.container{max-width:900px;margin:40px auto;padding:0 20px}.reading-circle{display:inline-block;width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#08137b,#4f09a7);color:#fff;display:grid;place-items:center;font-weight:700}</style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($article['title']); ?></h1>
        <div style="color:#666;margin-bottom:12px;"><?php echo date('Y-m-d', strtotime($article['publish_date'])); ?> • بواسطة <?php echo htmlspecialchars($article['author']); ?> • <span class="reading-circle"><?php echo htmlspecialchars($article['reading_time'] ?: '—'); ?></span> دقيقة قراءة</div>
        <?php if ($article['image_url']): ?><img src="<?php echo htmlspecialchars($article['image_url']); ?>" style="width:100%;border-radius:12px;margin-bottom:18px;object-fit:cover"><?php endif; ?>
        <div style="line-height:1.8"><?php echo $article['content']; ?></div>
    </div>
</body>
</html>
