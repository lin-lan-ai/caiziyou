<?php
/**
 * 菜籽游官网 - 注册页面
 */

require_once __DIR__ . '/../includes/config.php';

// 如果用户已经登录，重定向到首页
if (isLoggedIn()) {
    redirect('/');
}

// 检查是否开放注册
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'registration_enabled'");
$stmt->execute();
$result = $stmt->get_result();
$registration_enabled = $result->fetch_assoc()['setting_value'] ?? 'true';
$stmt->close();

if ($registration_enabled !== 'true') {
    die('抱歉，注册功能暂时关闭，请稍后再试。');
}

$errors = [];
$success = '';
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => ''
];

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 验证CSRF令牌
    if (!validateCSRFToken($csrf_token)) {
        $errors[] = '安全令牌无效，请刷新页面重试。';
    } else {
        // 获取表单数据
        $form_data['username'] = sanitizeInput($_POST['username'] ?? '');
        $form_data['email'] = sanitizeInput($_POST['email'] ?? '');
        $form_data['full_name'] = sanitizeInput($_POST['full_name'] ?? '');
        $form_data['phone'] = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $agree_terms = isset($_POST['agree_terms']);
        
        // 验证用户名
        if (empty($form_data['username'])) {
            $errors[] = '请输入用户名。';
        } elseif (strlen($form_data['username']) < 3 || strlen($form_data['username']) > 50) {
            $errors[] = '用户名长度必须在3-50个字符之间。';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
            $errors[] = '用户名只能包含字母、数字和下划线。';
        } else {
            // 检查用户名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $form_data['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = '用户名已被使用，请选择其他用户名。';
            }
            $stmt->close();
        }
        
        // 验证邮箱
        if (empty($form_data['email'])) {
            $errors[] = '请输入邮箱地址。';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = '请输入有效的邮箱地址。';
        } else {
            // 检查邮箱是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $form_data['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = '邮箱地址已被注册，请使用其他邮箱。';
            }
            $stmt->close();
        }
        
        // 验证密码
        if (empty($password)) {
            $errors[] = '请输入密码。';
        } elseif (strlen($password) < 8) {
            $errors[] = '密码长度至少为8个字符。';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = '密码必须包含大小写字母和数字。';
        } elseif ($password !== $confirm_password) {
            $errors[] = '两次输入的密码不一致。';
        }
        
        // 验证手机号（可选）
        if (!empty($form_data['phone']) && !preg_match('/^1[3-9]\d{9}$/', $form_data['phone'])) {
            $errors[] = '请输入有效的手机号码。';
        }
        
        // 验证服务条款
        if (!$agree_terms) {
            $errors[] = '请阅读并同意服务条款。';
        }
        
        // 如果没有错误，创建用户
        if (empty($errors)) {
            try {
                $conn->begin_transaction();
                
                // 哈希密码
                $password_hash = hashPassword($password);
                
                // 获取默认用户角色
                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'default_user_role'");
                $stmt->execute();
                $result = $stmt->get_result();
                $default_role = $result->fetch_assoc()['setting_value'] ?? 'user';
                $stmt->close();
                
                // 插入用户
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, phone, role) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssss", 
                    $form_data['username'], 
                    $form_data['email'], 
                    $password_hash,
                    $form_data['full_name'],
                    $form_data['phone'],
                    $default_role
                );
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // 创建用户资料
                    $stmt = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 生成邮箱验证令牌
                    $verification_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24小时有效
                    
                    $stmt = $conn->prepare("
                        INSERT INTO email_verifications (user_id, verification_token, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->bind_param("iss", $user_id, $verification_token, $expires_at);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 记录用户活动
                    logUserActivity($user_id, '用户注册', $_SERVER['REMOTE_ADDR']);
                    
                    $conn->commit();
                    
                    // 自动登录用户
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $form_data['username'];
                    $_SESSION['user_role'] = $default_role;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // 重定向到欢迎页面
                    redirect('/welcome.php');
                    
                } else {
                    throw new Exception("用户创建失败: " . $stmt->error);
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = '注册失败，请稍后重试。';
                error_log("注册错误: " . $e->getMessage());
            }
        }
    }
}

// 生成CSRF令牌
$csrf_token = generateCSRFToken();

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
    <title>注册 - <?php echo htmlspecialchars($site_name); ?></title>
    
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
            max-width: 500px;
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
            max-height: 70vh;
            overflow-y: auto;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
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
        
        .form-label .required {
            color: var(--danger-color);
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
        
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: var(--danger-color);
            transition: all 0.3s ease;
        }
        
        .strength-fill.weak {
            width: 33%;
            background: var(--danger-color);
        }
        
        .strength-fill.medium {
            width: 66%;
            background: var(--warning-color);
        }
        
        .strength-fill.strong {
            width: 100%;
            background: var(--success-color);
        }
        
        .strength-text {
            font-size: 0.8rem;
            color: var(--gray-color);
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid var(--border-color);
            cursor: pointer;
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .checkbox-group label {
            font-size: 0.9rem;
            color: var(--dark-color);
            cursor: pointer;
            line-height: 1.4;
        }
        
        .checkbox-group label a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .checkbox-group label a:hover {
            text-decoration: underline;
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
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
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
        }
        
        @media (max-width: 480px) {
            .auth-body {
                max-height: 60vh;
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
                    <span                    <span><?php echo htmlspecialchars($site_name); ?></span>
                </div>
                <h1 class="auth-title">创建新账户</h1>
                <p class="auth-subtitle">加入我们，发现精彩游戏世界</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars(implode(' ', $errors)); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="register-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">
                                用户名 <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control <?php echo !empty($errors) && (strpos(implode(' ', $errors), '用户名') !== false) ? 'error' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                                   required
                                   autocomplete="username"
                                   autofocus>
                            <div class="form-text">3-50个字符，只能包含字母、数字和下划线</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">
                                邮箱地址 <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control <?php echo !empty($errors) && (strpos(implode(' ', $errors), '邮箱') !== false) ? 'error' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                   required
                                   autocomplete="email">
                            <div class="form-text">用于登录和接收重要通知</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name" class="form-label">真实姓名</label>
                            <input type="text" 
                                   id="full_name" 
                                   name="full_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                                   autocomplete="name">
                            <div class="form-text">可选，用于个性化显示</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">手机号码</label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control <?php echo !empty($errors) && (strpos(implode(' ', $errors), '手机') !== false) ? 'error' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                                   autocomplete="tel">
                            <div class="form-text">可选，用于账户安全验证</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                密码 <span class="required">*</span>
                            </label>
                            <div class="password-toggle">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control <?php echo !empty($errors) && (strpos(implode(' ', $errors), '密码') !== false) ? 'error' : ''; ?>" 
                                       required
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" id="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strength-fill"></div>
                                </div>
                                <div class="strength-text" id="strength-text">密码强度：无</div>
                            </div>
                            <div class="form-text">至少8个字符，包含大小写字母和数字</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                确认密码 <span class="required">*</span>
                            </label>
                            <div class="password-toggle">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control <?php echo !empty($errors) && (strpos(implode(' ', $errors), '密码不一致') !== false) ? 'error' : ''; ?>" 
                                       required
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" id="toggle-confirm-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">请再次输入密码</div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="agree_terms" name="agree_terms" <?php echo isset($_POST['agree_terms']) ? 'checked' : ''; ?>>
                        <label for="agree_terms">
                            我已阅读并同意
                            <a href="/terms.php" target="_blank">服务条款</a>
                            和
                            <a href="/privacy.php" target="_blank">隐私政策</a>
                            <span class="required">*</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>注册账户</span>
                    </button>
                </form>
            </div>
            
            <div class="auth-footer">
                <div>已有账户？</div>
                <div class="auth-links">
                    <a href="/login.php" class="auth-link">立即登录</a>
                    <a href="/" class="auth-link">返回首页</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 密码显示/隐藏切换
        const togglePassword = document.getElementById('toggle-password');
        const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordIcon = togglePassword.querySelector('i');
        const confirmPasswordIcon = toggleConfirmPassword.querySelector('i');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        
        toggleConfirmPassword.addEventListener('click', () => {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            confirmPasswordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        
        // 密码强度检测
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        
        function checkPasswordStrength(password) {
            let score = 0;
            
            // 长度检查
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            
            // 字符类型检查
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            
            // 更新显示
            if (password.length === 0) {
                strengthFill.className = 'strength-fill';
                strengthFill.style.width = '0%';
                strengthText.textContent = '密码强度：无';
            } else if (score <= 2) {
                strengthFill.className = 'strength-fill weak';
                strengthText.textContent = '密码强度：弱';
            } else if (score <= 4) {
                strengthFill.className = 'strength-fill medium';
                strengthText.textContent = '密码强度：中';
            } else {
                strengthFill.className = 'strength-fill strong';
                strengthText.textContent = '密码强度：强';
            }
        }
        
        passwordInput.addEventListener('input', () => {
            checkPasswordStrength(passwordInput.value);
            
            // 实时验证密码匹配
            if (confirmPasswordInput.value) {
                validatePasswordMatch();
            }
        });
        
        // 密码匹配验证
        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordInput.classList.add('error');
                return false;
            } else {
                confirmPasswordInput.classList.remove('error');
                return true;
            }
        }
        
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        
        // 用户名实时验证
        const usernameInput = document.getElementById('username');
        let usernameTimeout;
        
        usernameInput.addEventListener('input', () => {
            clearTimeout(usernameTimeout);
            usernameTimeout = setTimeout(() => {
                const username = usernameInput.value.trim();
                
                if (username.length >= 3 && username.length <= 50 && /^[a-zA-Z0-9_]+$/.test(username)) {
                    // 可以在这里添加AJAX检查用户名是否可用
                    console.log('用户名格式正确:', username);
                }
            }, 500);
        });
        
        // 表单验证
        const registerForm = document.getElementById('register-form');
        const submitBtn = document.getElementById('submit-btn');
        
        registerForm.addEventListener('submit', (e) => {
            let valid = true;
            
            // 清除之前的错误状态
            const inputs = registerForm.querySelectorAll('.form-control');
            inputs.forEach(input => input.classList.remove('error'));
            
            // 验证必填字段
            const requiredFields = ['username', 'email', 'password', 'confirm_password'];
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.classList.add('error');
                    if (valid) field.focus();
                    valid = false;
                }
            });
            
            // 验证用户名格式
            const username = usernameInput.value.trim();
            if (username && (username.length < 3 || username.length > 50 || !/^[a-zA-Z0-9_]+$/.test(username))) {
                usernameInput.classList.add('error');
                if (valid) usernameInput.focus();
                valid = false;
            }
            
            // 验证邮箱格式
            const email = document.getElementById('email').value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('email').classList.add('error');
                if (valid) document.getElementById('email').focus();
                valid = false;
            }
            
            // 验证密码强度
            const password = passwordInput.value;
            if (password && password.length < 8) {
                passwordInput.classList.add('error');
                if (valid) passwordInput.focus();
                valid = false;
            }
            
            // 验证密码匹配
            if (!validatePasswordMatch()) {
                if (valid) confirmPasswordInput.focus();
                valid = false;
            }
            
            // 验证服务条款
            if (!document.getElementById('agree_terms').checked) {
                showError('请阅读并同意服务条款。');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                showError('请正确填写所有必填字段。');
            } else {
                // 防止重复提交
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>注册中...</span>';
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
        const formInputs = registerForm.querySelectorAll('input[required]');
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                if (input.value.trim()) {
                    input.classList.remove('error');
                }
            });
        });
        
        // 服务条款复选框验证
        const agreeTermsCheckbox = document.getElementById('agree_terms');
        agreeTermsCheckbox.addEventListener('change', () => {
            if (agreeTermsCheckbox.checked) {
                // 可以在这里添加一些视觉反馈
                console.log('服务条款已同意');
            }
        });
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
            
            // 检查是否有自动填充的值
            setTimeout(() => {
                if (passwordInput.value) {
                    checkPasswordStrength(passwordInput.value);
                }
            }, 200);
        });
        
        // 回车键提交表单
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                // 检查是否所有必填字段都已填写
                const allFilled = Array.from(formInputs).every(input => input.value.trim());
                if (allFilled && agreeTermsCheckbox.checked) {
                    registerForm.requestSubmit();
                }
            }
        });
        
        // 表单字段自动聚焦
        const fields = ['username', 'email', 'full_name', 'phone', 'password', 'confirm_password'];
        fields.forEach((fieldId, index) => {
            const field = document.getElementById(fieldId);
            field.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && index < fields.length - 1) {
                    e.preventDefault();
                    const nextField = document.getElementById(fields[index + 1]);
                    if (nextField) {
                        nextField.focus();
                    }
                }
            });
        });
    </script>
</body>
</html>