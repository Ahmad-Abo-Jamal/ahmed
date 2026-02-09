<?php
require_once '../config.php';
requireAdmin();

$admin = getAdminInfo($db, $_SESSION['admin_id']);
$page = $_GET['page'] ?? 'dashboard';

// Get dashboard statistics
try {
    $stats = [
        'messages' => $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'")->fetch()['count'],
        'articles' => $db->query("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")->fetch()['count'],
        'services' => $db->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch()['count'],
        'total_messages' => $db->query("SELECT COUNT(*) as count FROM contact_messages")->fetch()['count']
    ];
} catch(PDOException $e) {
    $stats = ['messages' => 0, 'articles' => 0, 'services' => 0, 'total_messages' => 0];
    $stats = ['messages' => 0, 'articles' => 0, 'services' => 0, 'total_messages' => 0, 'sectors' => 0, 'brands' => 0];
}

// Logout handler
if (isset($_GET['logout'])) {
    logActivity($db, $_SESSION['admin_id'], 'logout', 'تسجيل خروج');
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>لوحة التحكم</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #08137b;
            --secondary-purple: #4f09a7;
            --accent-gold: #c5a47e;
            --bg-light: #f5f6fa;
            --text-dark: #2c3e50;
            --border-color: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-blue) 0%, var(--secondary-purple) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .logo {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo i {
            font-size: 40px;
            color: var(--accent-gold);
            margin-bottom: 10px;
        }
        
        .logo h2 {
            font-size: 24px;
        }
        
        .menu {
            padding: 20px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-right-color: var(--accent-gold);
        }
        
        .menu-item i {
            width: 30px;
            font-size: 18px;
        }
        
        .badge {
            margin-right: auto;
            background: var(--accent-gold);
            color: var(--primary-blue);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-right: 280px;
        }
        
        .topbar {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .page-title h1 {
            font-size: 28px;
            color: var(--primary-blue);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 700;
        }
        
        .admin-details {
            text-align: right;
        }
        
        .admin-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .admin-role {
            font-size: 14px;
            color: #999;
        }
        
        .content-area {
            padding: 30px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-right: 70px;
            }
            
            .logo h2, .menu-item span, .badge {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
            }
        }
    </style>
    <script src="/static/js/jquery-3.6.0.min.js"></script>
    <script src="/static/js/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-shield-halved"></i>
                <h2>لوحة التحكم</h2>
            </div>
            
            <nav class="menu">
                <a href="?page=dashboard" class="menu-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                </a>
                
                <a href="?page=messages" class="menu-item <?php echo $page === 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>الرسائل</span>
                    <?php if ($stats['messages'] > 0): ?>
                        <span class="badge"><?php echo $stats['messages']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?page=articles" class="menu-item <?php echo $page === 'articles' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>المقالات</span>
                </a>
                
                <a href="?page=services" class="menu-item <?php echo $page === 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i>
                    <span>الخدمات</span>
                </a>
                
                <a href="?page=settings" class="menu-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                                    <a href="?page=sectors" class="menu-item <?php echo $page === 'sectors' ? 'active' : ''; ?>">
                                        <i class="fas fa-layer-group"></i>
                                        <span>القطاعات و العلامات</span>
                                    </a>
                
                                    <a href="?page=settings" class="menu-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </a>
                
                <a href="?page=activity" class="menu-item <?php echo $page === 'activity' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>سجل النشاطات</span>
                </a>
                
                <a href="?logout=1" class="menu-item" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <div class="page-title">
                    <h1>
                        <?php
                        $titles = [
                            'dashboard' => 'لوحة التحكم الرئيسية',
                            'messages' => 'إدارة الرسائل',
                            'articles' => 'إدارة المقالات',
                            'services' => 'إدارة الخدمات',
                            'settings' => 'الإعدادات',
                            'activity' => 'سجل النشاطات'
                        ];
                        echo $titles[$page] ?? 'لوحة التحكم';
                        ?>
                    </h1>
                </div>
                
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo mb_substr($admin['full_name'], 0, 1); ?>
                    </div>
                    <div class="admin-details">
                        <div class="admin-name"><?php echo sanitize($admin['full_name']); ?></div>
                        <div class="admin-role"><?php echo sanitize($admin['role']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <?php
                $page_file = "pages/{$page}.php";
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    include 'pages/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    
</body>
</html>