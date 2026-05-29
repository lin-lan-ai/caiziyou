<?php
/**
 * 菜籽游x纵流社群 - 注册页面
 */
require_once __DIR__ . '/../includes/community_config.php';

// 如果用户已经登录，重定向到首页
if (isCommunityLoggedIn()) {
    communityRedirect('/index_app.php');
}

$error = '';
$success = '';

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 验证CSRF令牌
    if (!validateCommunityCSRFToken($csrf_token)) {
        $error = '安全令牌无效，请刷新页面重试。';
    } else {
        $username = sanitizeCommunityInput($_POST['username'] ?? '');
        $email = sanitizeCommunityInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $nickname = sanitizeCommunityInput($_POST['nickname'] ?? '');
        $user_note = sanitizeCommunityInput($_POST['user_note'] ?? '');
        $agree_terms = isset($_POST['agree_terms']);
        
        // 验证输入
        $validation_errors = [];
        
        // 用户名验证
        if (empty($username)) {
            $validation_errors[] = '请输入用户名。';
        } elseif (strlen($username) < 3 || strlen($username) > 20) {
            $validation_errors[] = '用户名长度必须在3-20个字符之间。';
        } elseif (ctype_digit($username)) {
            $validation_errors[] = '用户名不能为纯数字。';
        }
        
        // 邮箱验证
        if (empty($email)) {
            $validation_errors[] = '请输入邮箱地址。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = '请输入有效的邮箱地址。';
        }
        
        // 密码验证
        if (empty($password)) {
            $validation_errors[] = '请输入密码。';
        } elseif (strlen($password) < 8) {
            $validation_errors[] = '密码长度至少为8个字符。';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $validation_errors[] = '密码必须包含大小写字母和数字。';
        } elseif ($password !== $password_confirm) {
            $validation_errors[] = '两次输入的密码不一致。';
        }
        
        // 昵称验证
        if (empty($nickname)) {
            $nickname = $username; // 默认使用用户名作为昵称
        } elseif (strlen($nickname) > 30) {
            $validation_errors[] = '昵称长度不能超过30个字符。';
        }
        
        // 服务条款
        if (!$agree_terms) {
            $validation_errors[] = '请阅读并同意服务条款。';
        }
        
        if (empty($validation_errors)) {
            $conn = getCommunityDBConnection();
            
            // 检查用户名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validation_errors[] = '用户名已被使用。';
            }
            $stmt->close();
            
            // 检查邮箱是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validation_errors[] = '邮箱地址已被使用。';
            }
            $stmt->close();
        }
        
        if (empty($validation_errors)) {
            // 哈希密码
            $password_hash = hashCommunityPassword($password);
            
            // 读取自动审核设置
            $autoApprove = false;
            $res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_approve_user'");
            if ($res && $row = $res->fetch_assoc()) {
                $autoApprove = ($row['setting_value'] === 'true');
            }
            $regStatus = $autoApprove ? 'approved' : 'pending';
            // 创建用户
            $unique_id = 'U' . time() . rand(1000, 9999);
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password_hash, full_name, nickname, user_note, registration_status, unique_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssssssss", $username, $email, $password_hash, $full_name, $nickname, $user_note, $regStatus, $unique_id);
            $full_name = $nickname;
            $stmt->bind_param("sssssss", $username, $email, $password_hash, $full_name, $nickname, $user_note, $unique_id);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $stmt->close();
                
                // 创建用户个人资料
                $stmt = $conn->prepare("
                    INSERT INTO user_profiles (user_id, created_at) 
                    VALUES (?, NOW())
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
                
                
                // 设置会话变量（但不自动登录）
                $_SESSION['community_registered'] = true;
                $_SESSION['community_registered_user'] = $username;
                
                // 注册活动记录已移除（不再自动发动态）
                
                // 显示成功消息，延迟重定向
                // 用户可以看到注册成功提示后再跳转
                $success = '注册成功！您的账户正在等待管理员审核，审核通过后即可登录。<br><br><strong>📢 温馨提示：</strong>此账户注册后，<strong>「纵流」旗下所有合作平台</strong>均可凭此账号登录使用，无需重复注册。';
                
                // 记录注册日志
                logCommunityActivity($user_id, '注册', '新用户注册，等待管理员审核');
            } else {
                $error = '注册失败，请稍后重试。';
            }
        } else {
            $error = implode('<br>', $validation_errors);
        }
    }
}

// 生成CSRF令牌
$csrf_token = generateCommunityCSRFToken();

// 获取系统设置
$siteName = getSystemSetting('site_name', '菜籽游x纵流社群');
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/community.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
            padding-top: 100px; /* 为顶部栏留出空间 */
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }
        
        .auth-card {
            width: 100%;
            max-width: 500px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-2xl);
            overflow: hidden;
            transition: all var(--transition-normal);
        }
        
        [data-theme="dark"] .auth-card {
            background: var(--gray-800);
        }
        
        .auth-header {
            padding: var(--spacing-2xl);
            text-align: center;
            background: linear-gradient(135deg, var(--success-color) 0%, #0da271 100%);
            color: var(--white);
        }
        
        .auth-logo {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        
        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        
        .auth-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .auth-body {
            padding: var(--spacing-2xl);
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        [data-theme="dark"] .form-label {
            color: var(--gray-300);
        }
        
        .form-label.required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 2px;
        }
        
        .form-input {
            padding: var(--spacing-md);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: all var(--transition-fast);
            background: var(--white);
            color: var(--gray-800);
        }
        
        [data-theme="dark"] .form-input {
            background: var(--gray-900);
            border-color: var(--gray-700);
            color: var(--gray-200);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-input.error {
            border-color: var(--danger-color);
        }
        
        .form-input.success {
            border-color: var(--success-color);
        }
        
        .password-strength {
            margin-top: var(--spacing-xs);
        }
        
        .strength-meter {
            height: 4px;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-bottom: var(--spacing-xs);
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: var(--danger-color);
            transition: all var(--transition-fast);
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
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        .form-checkbox {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            cursor: pointer;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--gray-400);
            cursor: pointer;
            transition: all var(--transition-fast);
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .form-checkbox input[type="checkbox"]:checked {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-checkbox span {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.4;
        }
        
        [data-theme="dark"] .form-checkbox span {
            color: var(--gray-400);
        }
        
        .form-checkbox a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .form-checkbox a:hover {
            text-decoration: underline;
        }
        
        .auth-footer {
            margin-top: var(--spacing-lg);
            text-align: center;
        }
        
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
        }
        
        .auth-link:hover {
            text-decoration: underline;
        }
        
        .auth-error {
            background: var(--danger-light);
            color: var(--danger-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        [data-theme="dark"] .auth-error {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .auth-success {
            background: var(--success-light);
            color: var(--success-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        [data-theme="dark"] .auth-success {
            background: rgba(16, 185, 129, 0.2);
        }
        
        /* 响应式设计 */
        @media (max-width: 480px) {
            .auth-container {
                padding: var(--spacing-md);
            }
            
            .auth-card {
                max-width: 100%;
            }
            
            .auth-header,
            .auth-body {
                padding: var(--spacing-xl);
            }
        }
        
        /* 动画效果 */
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
        
        .auth-card {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- 统一顶部栏 -->
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    
    <div class="auth-container">
        <div class="auth-card">
            <!-- 头部 -->
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="auth-title">加入<?php echo htmlspecialchars($siteName); ?></h1>
                <p class="auth-subtitle">开启你的社群之旅</p>
            </div>
            
            <!-- 主体 -->
            <div class="auth-body">
                <?php if ($error): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="auth-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="username">
                                <i class="fas fa-user"></i>
                                用户名
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-input" 
                                   placeholder="3-20个字符" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required
                                   autocomplete="username"
                                   autofocus>
                            <div class="form-hint" id="usernameHint"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="nickname">
                                <i class="fas fa-id-card"></i>
                                昵称
                            </label>
                            <input type="text" 
                                   id="nickname" 
                                   name="nickname" 
                                   class="form-input" 
                                   placeholder="可选，默认使用用户名" 
                                   value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>"
                                   autocomplete="nickname">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="email">
                            <i class="fas fa-envelope"></i>
                            邮箱地址
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="请输入有效的邮箱地址" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required
                               autocomplete="email">
                        <div class="form-hint" id="emailHint"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="password">
                                <i class="fas fa-lock"></i>
                                密码
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-input" 
                                   placeholder="至少8个字符，包含大小写和数字" 
                                   required
                                   autocomplete="new-password">
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">密码强度：无</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="password_confirm">
                                <i class="fas fa-lock"></i>
                                确认密码
                            </label>
                            <input type="password" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   class="form-input" 
                                   placeholder="请再次输入密码" 
                                   required
                                   autocomplete="new-password">
                            <div class="form-hint" id="passwordMatchHint"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="user_note">
                            <i class="fas fa-sticky-note"></i>
                            备注（选填）
                        </label>
                        <textarea 
                            id="user_note" 
                            name="user_note" 
                            class="form-input" 
                            placeholder="可填写注册目的、自我介绍等，供管理员参考" 
                            rows="3"
                            maxlength="500"><?php echo htmlspecialchars($_POST['user_note'] ?? ''); ?></textarea>
                        <div class="form-hint">最多500字符，管理员可见</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="agree_terms" id="agree_terms" required>
                            <span>
                                我已阅读并同意
                                <a href="terms.php" target="_blank">服务条款</a>
                                和
                                <a href="privacy.php" target="_blank">隐私政策</a>
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg" id="registerBtn">
                        <i class="fas fa-user-plus"></i>
                        <span>注册账户</span>
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>
                        已有账户？
                        <a href="login.php" class="auth-link">立即登录</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 表单验证
    const registerForm = document.getElementById('registerForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const agreeTermsCheckbox = document.getElementById('agree_terms');
    const registerBtn = document.getElementById('registerBtn');
    
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const usernameHint = document.getElementById('usernameHint');
    const emailHint = document.getElementById('emailHint');
    const passwordMatchHint = document.getElementById('passwordMatchHint');
    
    // 密码强度检测
    function checkPasswordStrength(password) {
        let strength = 0;
        
        // 长度检查
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        
        // 字符类型检查
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
        
        // 更新UI
        if (password.length === 0) {
            strengthFill.className = 'strength-fill';
            strengthFill.style.width = '0%';
            strengthText.textContent = '密码强度：无';
        } else if (strength <= 2) {
            strengthFill.className = 'strength-fill weak';
            strengthText.textContent = '密码强度：弱';
        } else if (strength <= 4) {
            strengthFill.className = 'strength-fill medium';
            strengthText.textContent = '密码强度：中等';
        } else {
            strengthFill.className = 'strength-fill strong';
            strengthText.textContent = '密码强度：强';
        }
    }
    
    // 用户名验证
    function validateUsername(username) {
        usernameHint.textContent = '';
        usernameInput.classList.remove('error', 'success');
        
        if (username.length === 0) {
            usernameHint.textContent = '请输入用户名';
            usernameInput.classList.add('error');
            return false;
        }
        
        if (username.length < 3 || username.length > 20) {
            usernameHint.textContent = '用户名长度必须在3-20个字符之间';
            usernameInput.classList.add('error');
            return false;
        }
        
        // 用户名不能纯数字
        if (/^\d+$/.test(username)) {
            usernameHint.textContent = '用户名不能为纯数字';
            usernameInput.classList.add('error');
            return false;
        }
        usernameHint.textContent = '格式正确';
        usernameInput.classList.add('success');
        return true;
        
        usernameInput.classList.add('success');
        return true;
    }
    
    // 邮箱验证
    function validateEmail(email) {
        emailHint.textContent = '';
        emailInput.classList.remove('error', 'success');
        
        if (email.length === 0) {
            emailHint.textContent = '请输入邮箱地址';
            emailInput.classList.add('error');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailHint.textContent = '请输入有效的邮箱地址';
            emailInput.classList.add('error');
            return false;
        }
        
        emailInput.classList.add('success');
        return true;
    }
    
    // 密码匹配验证
    function validatePasswordMatch() {
        passwordMatchHint.textContent = '';
        passwordConfirmInput.classList.remove('error', 'success');
        
        if (passwordConfirmInput.value.length === 0) {
            passwordMatchHint.textContent = '请确认密码';
            passwordConfirmInput.classList.add('error');
            return false;
        }
        
        if (passwordInput.value !== passwordConfirmInput.value) {
            passwordMatchHint.textContent = '两次输入的密码不一致';
            passwordConfirmInput.classList.add('error');
            return false;
        }
        
        passwordConfirmInput.classList.add('success');
        return true;
    }
    
    // 表单验证
    function validateForm() {
        let isValid = true;
        
        // 验证用户名
        if (!validateUsername(usernameInput.value)) {
            isValid = false;
        }
        
        // 验证邮箱
        if (!validateEmail(emailInput.value)) {
            isValid = false;
        }
        
        // 验证密码
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add('error');
            isValid = false;
        } else {
            passwordInput.classList.add('success');
        }
        
        // 验证密码匹配
        if (!validatePasswordMatch()) {
            isValid = false;
        }
        
        // 验证服务条款
        if (!agreeTermsCheckbox.checked) {
            agreeTermsCheckbox.parentElement.style.color = 'var(--danger-color)';
            isValid = false;
        } else {
            agreeTermsCheckbox.parentElement.style.color = '';
        }
        
        return isValid;
    }
    
    // 实时验证
    usernameInput.addEventListener('input', function() {
        validateUsername(this.value);
    });
    
    emailInput.addEventListener('input', function() {
        validateEmail(this.value);
    });
    
    passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        validatePasswordMatch();
    });
    
    passwordConfirmInput.addEventListener('input', validatePasswordMatch);
    
    agreeTermsCheckbox.addEventListener('change', function() {
        this.parentElement.style.color = this.checked ? '' : 'var(--danger-color)';
    });
    
    // 表单提交
    registerForm.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return;
        }
        
        // 显示加载状态
        const originalText = registerBtn.innerHTML;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>注册中...</span>';
        registerBtn.disabled = true;
        
        // 3秒后恢复（防止重复提交）
        setTimeout(() => {
            registerBtn.innerHTML = originalText;
            registerBtn.disabled = false;
        }, 3000);
    });
    
    // 回车键提交
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && (e.target === usernameInput || e.target === emailInput || 
            e.target === passwordInput || e.target === passwordConfirmInput)) {
            e.preventDefault();
            registerForm.requestSubmit();
        }
    });
    
    // 页面加载动画
    document.addEventListener('DOMContentLoaded', function() {
        // 卡片入场动画
        const authCard = document.querySelector('.auth-card');
        authCard.style.opacity = '0';
        authCard.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            authCard.style.transition = 'all 0.5s ease';
            authCard.style.opacity = '1';
            authCard.style.transform = 'translateY(0)';
        }, 100);
        
        // 自动聚焦用户名输入框
        if (!usernameInput.value) {
            usernameInput.focus();
        }
    });
    
    // 添加全局样式
    const style = document.createElement('style');
    style.textContent = `
        /* 提示文本样式 */
        .form-hint {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 2px;
            min-height: 16px;
        }
        
        [data-theme="dark"] .form-hint {
            color: var(--gray-400);
        }
        
        .form-hint.error {
            color: var(--danger-color);
        }
        
        .form-hint.success {
            color: var(--success-color);
        }
        
        /* 加载动画 */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 输入框动画 */
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input.error {
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        /* 按钮悬停效果 */
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* 响应式调整 */
        @media (max-height: 700px) {
            .auth-container {
                padding: var(--spacing-md);
            }
            
            .auth-header {
                padding: var(--spacing-lg);
            }
            
            .auth-body {
                padding: var(--spacing-lg);
            }
        }
        
        /* 打印样式 */
        @media print {
            .auth-container {
                background: none !important;
            }
            
            .auth-card {
                box-shadow: none !important;
                border: 1px solid var(--gray-300);
            }
            
            .btn {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // 异步检查用户名是否可用
    let usernameCheckTimeout;
    usernameInput.addEventListener('input', function() {
        clearTimeout(usernameCheckTimeout);
        
        const username = this.value.trim();
        if (username.length >= 3) {
            usernameCheckTimeout = setTimeout(() => {
                fetch('api/check_username.php?username=' + encodeURIComponent(username))
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            usernameHint.textContent = '✓ 用户名可用';
                            usernameHint.className = 'form-hint success';
                        } else {
                            usernameHint.textContent = '✗ 用户名已被使用';
                            usernameHint.className = 'form-hint error';
                        }
                    })
                    .catch(() => {
                        // 忽略网络错误
                    });
            }, 500);
        }
    });
    
    // 异步检查邮箱是否可用
    let emailCheckTimeout;
    emailInput.addEventListener('input', function() {
        clearTimeout(emailCheckTimeout);
        
        const email = this.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailRegex.test(email)) {
            emailCheckTimeout = setTimeout(() => {
                fetch('api/check_email.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            emailHint.textContent = '✓ 邮箱可用';
                            emailHint.className = 'form-hint success';
                        } else {
                            emailHint.textContent = '✗ 邮箱已被使用';
                            emailHint.className = 'form-hint error';
                        }
                    })
                    .catch(() => {
                        // 忽略网络错误
                    });
            }, 500);
        }
    });
    </script>
    <footer style="text-align:center;padding:16px 20px;background:#eeeef0;border-top:1px solid rgba(60,60,67,0.06);color:#7c7c82;font-size:13px;">
        &copy; 2026 菜籽游 &middot; 纵流 | 开放 &middot; 聚合 &middot; 创造
    </footer>
</body>
</html>