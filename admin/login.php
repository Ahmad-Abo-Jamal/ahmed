<?php
require_once '../config.php';

// Redirect if already logged in
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            // Check if account is locked
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check if locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
                    $error = "الحساب مقفل مؤقتاً. يرجى المحاولة بعد {$remaining} دقيقة";
                } else {
                    // Plain text password comparison
                    if ($password === $user['password']) {
                        // Reset login attempts
                        $stmt = $db->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id");
                        $stmt->execute([':id' => $user['id']]);
                        
                        // Set session
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_name'] = $user['full_name'];
                        $_SESSION['admin_role'] = $user['role'];
                        
                        // Log activity
                        logActivity($db, $user['id'], 'login', 'تسجيل دخول ناجح');
                        
                        header('Location: index.php');
                        exit;
                    } else {
                        // Increment failed attempts
                        $attempts = $user['login_attempts'] + 1;
                        
                        if ($attempts >= 5) {
                            // Lock account for 30 minutes
                            $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                            $stmt = $db->prepare("UPDATE admin_users SET login_attempts = :attempts, locked_until = :locked WHERE username = :username");
                            $stmt->execute([
                                ':attempts' => $attempts,
                                ':locked' => $locked_until,
                                ':username' => $username
                            ]);
                            $error = 'تم تجاوز عدد المحاولات المسموح بها. الحساب مقفل لمدة 30 دقيقة';
                        } else {
                            $stmt = $db->prepare("UPDATE admin_users SET login_attempts = :attempts WHERE username = :username");
                            $stmt->execute([':attempts' => $attempts, ':username' => $username]);
                            $remaining = 5 - $attempts;
                            $error = "كلمة المرور غير صحيحة. تبقى {$remaining} محاولات";
                        }
                    }
                }
            } else {
                $error = 'اسم المستخدم غير موجود';
            }
        } catch(PDOException $e) {
            $error = 'حدث خطأ في الاتصال بقاعدة البيانات';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #08137b 0%, #4f09a7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }
        
        .login-side {
            flex: 1;
            padding: 60px 50px;
            background: linear-gradient(135deg, #08137b 0%, #4f09a7 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-side i {
            font-size: 80px;
            margin-bottom: 30px;
            color: #c5a47e;
        }
        
        .login-side h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .login-side p {
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-form {
            flex: 1;
            padding: 60px 50px;
        }
        
        .form-header {
            margin-bottom: 40px;
        }
        
        .form-header h1 {
            color: #08137b;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 45px 15px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #08137b;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #08137b 0%, #4f09a7 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(8, 19, 123, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-side {
                padding: 40px 30px;
            }
            
            .login-form {
                padding: 40px 30px;
            }
            
            .login-side i {
                font-size: 60px;
            }
            
            .login-side h2 {
                font-size: 24px;
            }
            
            .form-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-side">
            <i class="fas fa-shield-halved"></i>
            <h2>لوحة التحكم الإدارية</h2>
            <p>مرحباً بك في لوحة التحكم الخاصة بموقعك. يرجى تسجيل الدخول للمتابعة.</p>
        </div>
        
        <div class="login-form">
            <div class="form-header">
                <h1>تسجيل الدخول</h1>
                <p>أدخل بيانات الدخول الخاصة بك</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <div class="input-group">
                        <input type="text" name="username" required autocomplete="username">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" name="password" required autocomplete="current-password">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>