<?php
/**
 * 菜籽游官网 - 欢迎页面
 */

require_once __DIR__ . '/../includes/config.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    redirect('/login.php');
}

// 获取用户信息
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT username, email, full_name, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// 获取网站设置
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'site_name'");
$stmt->execute();
$result = $stmt->get_result();
$site_name = $result->fetch_assoc()['setting_value'] ?? '菜籽游官网';
$stmt->close();
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎 - <?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .welcome-container {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--spacing-3xl) var(--spacing-lg);
        }
        
        .welcome-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--spacing-3xl);
            text-align: center;
        }
        
        [data-theme="dark"] .welcome-card {
            background: var(--gray-800);
        }
        
        .welcome-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-2xl);
        }
        
        .welcome-icon i {
            font-size: 3rem;
            color: white;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-lg);
            color: var(--dark-color);
        }
        
        [data-theme="dark"] .welcome-title {
            color: var(--gray-200);
        }
        
        .welcome-message {
            font-size: 1.125rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-2xl);
            line-height: 1.6;
        }
        
        .user-info {
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-2xl);
            text-align: left;
        }
        
        [data-theme="dark"] .user-info {
            background: var(--gray-900);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        [data-theme="dark"] .info-item {
            border-bottom-color: var(--gray-700);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray-700);
        }
        
        [data-theme="dark"] .info-label {
            color: var(--gray-300);
        }
        
        .info-value {
            color: var(--dark-color);
        }
        
        [data-theme="dark"] .info-value {
            color: var(--gray-200);
        }
        
        .welcome-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            margin-top: var(--spacing-2xl);
        }
        
        @media (max-width: 768px) {
            .welcome-card {
                padding: var(--spacing-xl);
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏（简化版） -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="/" class="logo">
                    <i class="fas fa-gamepad"></i>
                    <span><?php echo htmlspecialchars($site_name); ?></span>
                </a>
            </div>
            <div class="navbar-actions">
                <a href="/" class="btn btn-outline">返回首页</a>
            </div>
        </div>
    </nav>
    
    <main>
        <div class="welcome-container">
            <div class="welcome-card">
                <div class="welcome-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1 class="welcome-title">欢迎加入<?php echo htmlspecialchars($site_name); ?>！</h1>
                
                <p class="welcome-message">
                    感谢您注册<?php echo htmlspecialchars($site_name); ?>。您的账户已成功创建，现在可以开始探索我们的游戏世界了。
                </p>
                
                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">用户名：</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">邮箱：</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if ($user['full_name']): ?>
                    <div class="info-item">
                        <span class="info-label">姓名：</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">注册时间：</span>
                        <span class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">账户类型：</span>
                        <span class="info-value">
                            <?php 
                            switch ($user['role']) {
                                case 'admin': echo '管理员'; break;
                                case 'moderator': echo '版主'; break;
                                default: echo '普通用户';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="welcome-actions">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        <span>前往首页</span>
                    </a>
                    <a href="/games.php" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        <span>浏览游戏</span>
                    </a>
                    <a href="/profile.php" class="btn btn-outline">
                        <i class="fas fa-user"></i>
                        <span>完善资料</span>
                    </a>
                </div>
                
                <p style="margin-top: var(--spacing-xl); color: var(--gray-500); font-size: 0.875rem;">
                    提示：请尽快验证您的邮箱地址以确保账户安全。
                </p>
            </div>
        </div>
    </main>
    
    <script src="/assets/js/main.js"></script>
    <script>
        // 显示欢迎通知
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof showNotification === 'function') {
                    showNotification('欢迎加入！开始探索游戏世界吧！', 'success', 5000);
                }
            }, 1000);
        });
    </script>
</body>
</html>