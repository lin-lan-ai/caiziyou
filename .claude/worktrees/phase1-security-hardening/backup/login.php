<?php
/**
 * 菜籽游官网 - 登录页面
 */

require_once __DIR__ . '/../includes/config.php';

// 如果用户已经登录，重定向到首页
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 验证CSRF令牌
    if (!validateCSRFToken($csrf_token)) {
        $error = '安全令牌无效，请刷新页面重试。';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码。';
        } else {
            $conn = getDBConnection();
            
            // 检查用户是否存在
            $stmt = $conn->prepare("
                SELECT id, username, email, password_hash, role, status, login_attempts, lockout_until 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                // 检查账户状态
                if ($user['status'] !== 'active') {
                    switch ($user['status']) {
                        case 'inactive':
                            $error = '账户未激活，请检查邮箱激活账户。';
                            break;
                        case 'suspended':
                            $error = '账户已被暂停，请联系客服。';
                            break;
                        case 'banned':
                            $error = '账户已被封禁。';
                            break;
                        default:
                            $error = '账户状态异常。';
                    }
                }
                // 检查账户是否被锁定
                elseif ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
                    $lockout_time = strtotime($user['lockout_until']) - time();
                    $minutes = ceil($lockout_time / 60);
                    $error = "账户已被锁定，请{$minutes}分钟后再试。";
                }
                // 验证密码
                elseif (verifyPassword($password, $user['password_hash'])) {
                    // 登录成功，重置登录尝试次数
                    $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL, last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 设置会话变量
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // 记录用户活动
                    logUserActivity($user['id'], '用户登录', $_SERVER['REMOTE_ADDR']);
                    
                    // 设置记住我cookie
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30天
                        
                        $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))");
                        $stmt->bind_param("isssi", $user['id'], $token, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $expires);
                        $stmt->execute();
                        $stmt->close();
                        
                        setcookie('remember_token', $token, $expires, '/', '', true, true);
                    }
                    
                    // 重定向到之前访问的页面或首页
                    $redirect_url = $_SESSION['redirect_url'] ?? '/';
                    unset($_SESSION['redirect_url']);
                    redirect($redirect_url);
                    
                } else {
                    // 密码错误，增加登录尝试次数
                    $login_attempts = $user['login_attempts'] + 1;
                    $max_attempts = MAX_LOGIN_ATTEMPTS;
                    
                    if ($login_attempts >= $max_attempts) {
                        // 锁定账户
                        $lockout_until = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                        $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, lockout_until = ? WHERE id = ?");
                        $stmt->bind_param("isi", $login_attempts, $lockout_until, $user['id']);
                        $error = "密码错误次数过多，账户已被锁定15分钟。";
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                        $stmt->bind_param("ii", $login_attempts, $user['id']);
                        $remaining = $max_attempts - $login_attempts;
                        $error = "用户名或密码错误，还剩{$remaining}次尝试机会。";
                    }
                    
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $error = '用户名或密码错误。';
            }
        }
    }
}

// 生成CSRF令牌
$csrf_token = generateCSRFToken();

// 获取网站设置
$conn = getDBConnection();
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
    <title>登录 - <?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gray-color: #6b7280;
            --border-color: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 440px;
        }
        
        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        .auth-header {
            padding: 40px 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .auth-logo i {
            font-size: 2.5rem;
        }
        
        .auth-logo span {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .auth-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .auth-subtitle {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .auth-body {
            padding: 40px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: var(--danger-color);
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: var(--success-color);
            border: 1px solid #a7f3d0;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control.error {
            border-color: var(--danger-color);
        }
        
        .form-text {
            display: block;
            margin-top: 6px;
            font-size: 0.85rem;
            color: var(--gray-color);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid var(--border-color);
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 0.9rem;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .auth-footer {
            padding: 20px 40px;
            text-align: center;
            background: var(--light-color);
            border-top: 1px solid var(--border-color);
        }
        
        .auth-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 4px;
        }
        
        .social-login {
            margin-top: 30px;
        }
        
        .social-login-title {
            text-align: center;
            color: var(--gray-color);
            font-size: 0.9rem;
            margin-bottom: 15px;
            position: relative;
        }
        
        .social-login-title::before,
        .social-login-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: var(--border-color);
        }
        
        .social-login-title::before {
            left: 0;
        }
        
        .social-login-title::after {
            right: 0;
        }
        
        .social-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--dark-color);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .social-btn-qq {
            color: #12b7f5;
            border-color: #12b7f5;
        }
        
        .social-btn-wechat {
            color: #07c160;
            border-color: #07c160;
        }
        
        .social-btn-weibo {
            color: #e6162d;
            border-color: #e6162d;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @media (max-width: 480px) {
            .auth-header,
            .auth-body,
            .auth-footer {
                padding: 30px 20px;
            }
            
            .auth-logo span {
                font-size: 1.5rem;
            }
            
            .auth-title {
                font-size: 1.3rem;
            }
            
            .social-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-gamepad"></i>
                    <span><?php echo htmlspecialchars($site_name); ?></span>
                </div>
                <h1 class="auth-title">欢迎回来</h1>
                <p class="auth-subtitle">登录您的账户继续使用</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">用户名或邮箱</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control <?php echo $error && strpos($error, '用户名') !== false ? 'error' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required
                               autocomplete="username"
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">                        <label for="password" class="form-label">密码</label>
                        <div class="password-toggle">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control <?php echo $error && strpos($error, '密码') !== false ? 'error' : ''; ?>" 
                                   required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle-btn" id="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            <a href="/forgot-password.php" class="auth-link">忘记密码？</a>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">记住我（30天内自动登录）</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>登录</span>
                    </button>
                    
                    <div class="social-login">
                        <div class="social-login-title">或使用第三方登录</div>
                        <div class="social-buttons">
                            <button type="button" class="social-btn social-btn-qq">
                                <i class="fab fa-qq"></i>
                                <span>QQ登录</span>
                            </button>
                            <button type="button" class="social-btn social-btn-wechat">
                                <i class="fab fa-weixin"></i>
                                <span>微信登录</span>
                            </button>
                            <button type="button" class="social-btn social-btn-weibo">
                                <i class="fab fa-weibo"></i>
                                <span>微博登录</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="auth-footer">
                <div>还没有账户？</div>
                <div class="auth-links">
                    <a href="/register.php" class="auth-link">立即注册</a>
                    <a href="/" class="auth-link">返回首页</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 密码显示/隐藏切换
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const passwordIcon = togglePassword.querySelector('i');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        
        // 表单验证
        const loginForm = document.getElementById('login-form');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        loginForm.addEventListener('submit', (e) => {
            let valid = true;
            
            // 清除之前的错误状态
            usernameInput.classList.remove('error');
            passwordInput.classList.remove('error');
            
            // 验证用户名
            if (!usernameInput.value.trim()) {
                usernameInput.classList.add('error');
                usernameInput.focus();
                valid = false;
            }
            
            // 验证密码
            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('error');
                if (valid) passwordInput.focus();
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                showError('请填写所有必填字段。');
            }
        });
        
        // 显示错误消息
        function showError(message) {
            // 移除现有的错误消息
            const existingAlert = document.querySelector('.alert-danger');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // 创建新的错误消息
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            
            // 插入到表单前面
            const authBody = document.querySelector('.auth-body');
            const firstChild = authBody.firstChild;
            authBody.insertBefore(alertDiv, firstChild);
            
            // 添加动画
            setTimeout(() => {
                alertDiv.style.animation = 'slideIn 0.3s ease-out';
            }, 10);
        }
        
        // 输入框实时验证
        usernameInput.addEventListener('input', () => {
            if (usernameInput.value.trim()) {
                usernameInput.classList.remove('error');
            }
        });
        
        passwordInput.addEventListener('input', () => {
            if (passwordInput.value.trim()) {
                passwordInput.classList.remove('error');
            }
        });
        
        // 第三方登录按钮点击事件
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const platform = btn.classList.contains('social-btn-qq') ? 'QQ' :
                               btn.classList.contains('social-btn-wechat') ? '微信' : '微博';
                
                showError(`${platform}登录功能正在开发中，请使用账号密码登录。`);
            });
        });
        
        // 回车键提交表单
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && (e.target === usernameInput || e.target === passwordInput)) {
                loginForm.requestSubmit();
            }
        });
        
        // 自动填充检测
        document.addEventListener('DOMContentLoaded', () => {
            // 检查是否有自动填充的值
            setTimeout(() => {
                if (usernameInput.value || passwordInput.value) {
                    usernameInput.classList.remove('error');
                    passwordInput.classList.remove('error');
                }
            }, 100);
        });
        
        // 记住我功能提示
        const rememberCheckbox = document.getElementById('remember');
        rememberCheckbox.addEventListener('change', () => {
            if (rememberCheckbox.checked) {
                // 可以在这里添加一些视觉反馈
                console.log('记住我功能已启用');
            }
        });
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
        
        // 防止重复提交
        let isSubmitting = false;
        loginForm.addEventListener('submit', () => {
            if (isSubmitting) {
                return false;
            }
            isSubmitting = true;
            
            // 禁用提交按钮
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>登录中...</span>';
            submitBtn.disabled = true;
            
            // 3秒后恢复（防止无限等待）
            setTimeout(() => {
                if (isSubmitting) {
                    isSubmitting = false;
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    showError('登录请求超时，请重试。');
                }
            }, 10000);
            
            return true;
        });
    </script>
</body>
</html>