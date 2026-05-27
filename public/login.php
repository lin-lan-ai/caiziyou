<?php
/**
 * 菜籽游x纵流社群 - 极客风格登录页面
 */
require_once __DIR__ . '/../includes/community_config.php';

// 如果用户已经登录，重定向到首页
if (isCommunityLoggedIn()) {
    communityRedirect('/index_app.php');
}

$error = '';
$success = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 验证CSRF令牌
    if (!validateCommunityCSRFToken($csrf_token)) {
        $error = '安全令牌无效，请刷新页面重试。';
    } else {
        $username = sanitizeCommunityInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码。';
        } else {
            $conn = getCommunityDBConnection();
            
            // 检查用户是否存在
            $stmt = $conn->prepare("
                SELECT id, username, email, password_hash, nickname, role, status, registration_status 
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
                            $error = '账户未激活，请联系管理员。';
                            break;
                        case 'banned':
                            $error = '账户已被封禁。';
                            break;
                        default:
                            $error = '账户状态异常。';
                    }
                }
                // 检查是否已通过审核
                elseif ($user['registration_status'] !== 'approved') {
                    $error = '账户正在等待管理员审核，请稍后重试。';
                }
                // 验证密码
                elseif (verifyCommunityPassword($password, $user['password_hash'])) {
                    // 登录成功
                    $_SESSION['community_user_id'] = $user['id'];
                    $_SESSION['community_user_role'] = $user['role'];
                    $_SESSION['community_username'] = $user['username'];
                    $_SESSION['community_logged_in'] = true;
                    $_SESSION['community_login_time'] = time();
                    
                    // 更新最后登录时间
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 记录用户活动
                    logCommunityActivity($user['id'], '登录', '用户登录成功');
                    
                    // ===== 单设备登录：踢掉同一用户的所有旧会话 =====
                    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 设置记住我cookie
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30天
                        
                        // 创建新会话
                        $stmt = $conn->prepare("
                            INSERT INTO user_sessions (user_id, session_token, expires_at) 
                            VALUES (?, ?, FROM_UNIXTIME(?))
                        ");
                        $stmt->bind_param("isi", $user['id'], $token, $expires);
                        $stmt->execute();
                        $stmt->close();
                        
                        setcookie('community_remember_token', $token, $expires, '/', '', true, true);
                    }
                    
                    // 重定向到之前访问的页面或首页
                    $redirect_url = $_SESSION['community_redirect_url'] ?? '/index_app.php';
                    unset($_SESSION['community_redirect_url']);
                    communityRedirect($redirect_url);
                    
                } else {
                    $error = '用户名或密码错误。';
                }
            } else {
                $error = '用户名或密码错误。';
            }
        }
    }
}

// 生成CSRF令牌
$csrf_token = generateCommunityCSRFToken();

// 获取系统设置
$siteName = getSystemSetting('site_name', '菜籽游x纵流社群');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>菜籽游 ヽ(✿ﾟ▽ﾟ)ノ</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/geek.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* 页面特定样式 */
        body {
            background: none;
            overflow: hidden;
        }
        
        /* Canvas背景 */
        #bgCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        /* 登录容器动画 */
        .login-container {
            animation: pulse 2s ease-in-out infinite;
            margin-top: 80px; /* 为顶部栏留出空间 */
        }
        
        .login-container:hover {
            animation: none;
        }
        
        /* 错误消息样式 */
        .login-error {
            width: 100%;
            padding: var(--spacing-sm);
            background: rgba(255, 39, 112, 0.2);
            border: 1px solid var(--neon-pink);
            color: var(--neon-pink);
            border-radius: var(--radius-none);
            font-size: 0.8em;
            text-align: center;
            margin-bottom: var(--spacing-md);
            display: none;
        }
        
        .login-error.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        /* 加载动画 */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid var(--neon-cyan);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: var(--spacing-sm);
        }
        
        .loading-spinner.active {
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
            opacity: 1;
            visibility: visible;
        }
        
        /* 响应式设计 */
        @media (max-width: 480px) {
            .login-container {
                width: 90% !important;
                max-width: 90% !important;
            }
            
            .login-container:hover,
            .login-container.expanded {
                width: 90% !important;
                height: 450px !important;
            }
            
            .login-form {
                width: 85% !important;
            }
            
            .modal-content {
                width: 90% !important;
                padding: var(--spacing-lg) var(--spacing-md);
            }
        }
        
        @media (max-height: 600px) {
            .login-container {
                height: 180px !important;
            }
            
            .login-container:hover,
            .login-container.expanded {
                height: 400px !important;
            }
            
            .login-box {
                inset: 50px !important;
            }
            
            .login-container:hover .login-box,
            .login-container.expanded .login-box {
                inset: 30px !important;
            }
        }
        
        /* 打印样式 */
        @media print {
            .login-container {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
            }
            
            .login-container::before,
            .login-container::after {
                display: none !important;
            }
            
            .login-box {
                background: #fff !important;
                color: #000 !important;
            }
            
            .form-input {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: #fff !important;
            }
            
            .btn {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: #fff !important;
            }
        }
    </style>
</head>
<body>
    <!-- 统一顶部栏 -->
    <!-- 登录页不需要顶部栏 -->
    
    <!-- Canvas背景 -->
    <canvas id="bgCanvas"></canvas>
    
    <div id="app">
        <!-- 登录页 -->
        <div id="login-page" class="page active">
            <div class="login-container" id="loginBox">
                <div class="login-box">
                    <div class="login-form" id="loginForm">
                        <h2 class="login-title">
                            <i class="fa-solid fa-right-to-bracket"></i> 菜籽游
                        </h2>
                        
                        <div class="login-error" id="loginError">
                            <?php if ($error): ?>
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="" id="loginFormSubmit">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="form-group">
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-input" 
                                       placeholder="「纵流」ID：" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required
                                       autocomplete="username"
                                       autofocus>
                            </div>
                            
                            <div class="form-group">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-input" 
                                       placeholder="  通行令牌：" 
                                       required
                                       autocomplete="current-password">
                            </div>
                            
                            <div class="form-group">
                                <label style="color: var(--text-secondary); font-size: 0.8em; display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                                    <input type="checkbox" name="remember" id="remember" checked style="width: auto;">
                                    <span>记住我</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                                <span>查验/check</span>
                                <div class="loading-spinner" id="loginSpinner"></div>
                            </button>
                        </form>
                        
                        <div class="form-links">
                            <span>入驻「纵流」？<a href="/register.php" style="color: var(--accent);">立即入驻</a></span>
                            <a href="#" id="adminLink">...</a>
                        </div>
                        <div style="margin-top:12px;font-size:0.8em;">
                            <a href="/forgot_password.php" style="color: var(--text-secondary); text-decoration: none;">忘记密码？</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 注册浮窗已移除，注册功能在 /register.php -->
    
    <script>
    // 页面加载完成
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化Canvas背景
        initCanvasBackground();
        
        // 登录框悬停效果
        const loginBox = document.getElementById('loginBox');
        const loginForm = document.querySelector('.login-form');
        
        // 自动展开登录框
        setTimeout(() => {
            loginBox.classList.add('expanded');
        }, 500);
        
        // 鼠标移入移出效果
        loginBox.addEventListener('mouseenter', () => {
            loginBox.classList.add('expanded');
        });
        
        loginBox.addEventListener('mouseleave', () => {
            // 不要自动收起，保持展开状态
        });
        
        // 表单验证
        const loginFormSubmit = document.getElementById('loginFormSubmit');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        const loginSpinner = document.getElementById('loginSpinner');
        const loginError = document.getElementById('loginError');
        
        // 显示错误消息
        function showError(message) {
            loginError.textContent = message;
            loginError.classList.add('show');
            
            // 5秒后自动隐藏
            setTimeout(() => {
                loginError.classList.remove('show');
            }, 5000);
        }
        
        // 表单提交
        loginFormSubmit.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 验证输入
            if (!usernameInput.value.trim()) {
                showError('请输入用户名');
                usernameInput.focus();
                return;
            }
            
            if (!passwordInput.value) {
                showError('请输入密码');
                passwordInput.focus();
                return;
            }
            
            // 显示加载状态 - 按钮旋转 + 边框旋转
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span>验证中...</span><div class="loading-spinner active"></div>';
            loginBox.classList.add('loading');
            loginBtn.classList.add('loading');
            
            // 提交表单（用小延迟让动画先播放）
            var self = this;
            setTimeout(function() { self.submit(); }, 200);
        });
        
        // 入驻链接已改为直接跳转到 /register.php
        
        // 管理员链接
        const adminLink = document.getElementById('adminLink');
        if (adminLink) {
            adminLink.addEventListener('click', function(e) {
                e.preventDefault();
                alert('管理员功能需要特殊权限，请联系系统管理员。');
            });
        }
        
        // 回车键提交
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                if (document.activeElement === usernameInput || 
                    document.activeElement === passwordInput) {
                    e.preventDefault();
                    loginFormSubmit.requestSubmit();
                }
            }
        });
        
        // 自动聚焦用户名输入框
        if (!usernameInput.value) {
            setTimeout(() => {
                usernameInput.focus();
            }, 1000);
        }
    });
    
    // Canvas背景动画
    function initCanvasBackground() {
        const canvas = document.getElementById('bgCanvas');
        const ctx = canvas.getContext('2d');
        
        // 设置Canvas尺寸
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // 粒子系统
        const particles = [];
        const particleCount = 100;
        
        // 创建粒子
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 0.5;
                this.speedX = Math.random() * 1 - 0.5;
                this.speedY = Math.random() * 1 - 0.5;
                this.color = `rgba(${Math.floor(Math.random() * 100 + 155)}, 
                                  ${Math.floor(Math.random() * 255)}, 
                                  ${Math.floor(Math.random() * 100 + 155)}, 
                                  ${Math.random() * 0.5 + 0.2})`;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                // 边界检查
                if (this.x > canvas.width) this.x = 0;
                else if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                else if (this.y < 0) this.y = canvas.height;
            }
            
            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                
                // 绘制连接线
                for (let i = 0; i < particles.length; i++) {
                    const dx = this.x - particles[i].x;
                    const dy = this.y - particles[i].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (distance < 100) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(69, 243, 255, ${0.1 * (1 - distance / 100)})`;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(this.x, this.y);
                        ctx.lineTo(particles[i].x, particles[i].y);
                        ctx.stroke();
                    }
                }
            }
        }
        
        // 初始化粒子
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
        
        // 动画循环
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // 绘制网格背景
            drawGrid();
            
            // 更新和绘制粒子
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            
            // 绘制中心光晕
            drawGlow();
            
            requestAnimationFrame(animate);
        }
        
        // 绘制网格背景
        function drawGrid() {
            const gridSize = 50;
            const offsetX = (Date.now() * 0.01) % gridSize;
            const offsetY = (Date.now() * 0.01) % gridSize;
            
            ctx.strokeStyle = 'rgba(46, 204, 113, 0.05)';
            ctx.lineWidth = 0.5;
            
            // 垂直线
            for (let x = offsetX; x < canvas.width; x += gridSize) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, canvas.height);
                ctx.stroke();
            }
            
            // 水平线
            for (let y = offsetY; y < canvas.height; y += gridSize) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
        }
        
        // 绘制中心光晕
        function drawGlow() {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const time = Date.now() * 0.001;
            
            // 创建径向渐变
            const gradient = ctx.createRadialGradient(
                centerX, centerY, 0,
                centerX, centerY, 300
            );
            
            gradient.addColorStop(0, 'rgba(69, 243, 255, 0.1)');
            gradient.addColorStop(0.5, 'rgba(255, 39, 112, 0.05)');
            gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
            
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(centerX, centerY, 300, 0, Math.PI * 2);
            ctx.fill();
            
            // 绘制脉冲圆环
            const pulseRadius = 100 + Math.sin(time) * 20;
            
            ctx.strokeStyle = `rgba(69, 243, 255, ${0.3 + Math.sin(time) * 0.2})`;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, centerY, pulseRadius, 0, Math.PI * 2);
            ctx.stroke();
            
            // 绘制旋转的三角形
            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(time);
            
            ctx.strokeStyle = `rgba(255, 39, 112, ${0.5 + Math.sin(time * 2) * 0.3})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            
            const triangleSize = 30;
            for (let i = 0; i < 3; i++) {
                const angle = (i * 2 * Math.PI) / 3;
                const x = Math.cos(angle) * triangleSize;
                const y = Math.sin(angle) * triangleSize;
                
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }
            
            ctx.closePath();
            ctx.stroke();
            ctx.restore();
        }
        
        // 开始动画
        animate();
        
        // 鼠标交互
        let mouseX = 0;
        let mouseY = 0;
        
        canvas.addEventListener('mousemove', function(e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            // 鼠标附近的粒子加速
            for (let i = 0; i < particles.length; i++) {
                const dx = particles[i].x - mouseX;
                const dy = particles[i].y - mouseY;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 100) {
                    const force = (100 - distance) / 100;
                    particles[i].speedX += (dx / distance) * force * 0.5;
                    particles[i].speedY += (dy / distance) * force * 0.5;
                }
            }
        });
        
        // 点击效果
        canvas.addEventListener('click', function(e) {
            // 在点击位置创建新粒子
            for (let i = 0; i < 5; i++) {
                const particle = new Particle();
                particle.x = e.clientX;
                particle.y = e.clientY;
                particle.speedX = Math.random() * 4 - 2;
                particle.speedY = Math.random() * 4 - 2;
                particle.size = Math.random() * 3 + 1;
                particle.color = `rgba(${Math.floor(Math.random() * 100 + 155)}, 
                                      ${Math.floor(Math.random() * 255)}, 
                                      ${Math.floor(Math.random() * 100 + 155)}, 
                                      0.8)`;
                particles.push(particle);
            }
            
            // 限制粒子数量
            if (particles.length > 200) {
                particles.splice(0, particles.length - 200);
            }
        });
    }
    
    // 添加全局样式
    const style = document.createElement('style');
    style.textContent = `
        /* 按钮加载动画 */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(0.98);
            }
        }
        
        .login-container {
            animation: pulse 2s ease-in-out infinite;
        }
        
        /* 输入框聚焦动画 */
        .form-input:focus {
            animation: inputFocus 0.3s ease;
        }
        
        @keyframes inputFocus {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* 错误消息动画 */
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(5px);
            }
        }
        
        .form-input.error {
            animation: shake 0.5s ease;
        }
        
        /* 模态框动画 */
        .modal-content {
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* 响应式调整 */
        @media (max-width: 768px) {
            .login-container::before,
            .login-container::after {
                display: none;
            }
            
            .login-container {
                background: var(--bg-tertiary) !important;
                border: 2px solid var(--neon-cyan) !important;
            }
            
            .login-box {
                background: rgba(0, 0, 0, 0.5) !important;
            }
        }
        
        /* 深色模式优化 */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #0a0a0a;
                --bg-secondary: #121212;
                --bg-tertiary: #1a1a1a;
            }
        }
        
        /* 高对比度模式 */
        @media (prefers-contrast: high) {
            :root {
                --neon-cyan: #00ffff;
                --neon-pink: #ff00ff;
                --neon-green: #00ff00;
                --text-primary: #ffffff;
                --text-secondary: #cccccc;
            }
            
            .form-input {
                border-width: 3px !important;
            }
        }
        
        /* 减少动画 */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .login-container {
                animation: none !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // 性能优化：只在标签页激活时运行动画
    let isPageVisible = true;
    
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        
        if (!isPageVisible) {
            // 页面不可见时暂停Canvas动画
            const canvas = document.getElementById('bgCanvas');
            if (canvas) {
                canvas.style.display = 'none';
            }
        } else {
            // 页面可见时恢复Canvas动画
            const canvas = document.getElementById('bgCanvas');
            if (canvas) {
                canvas.style.display = 'block';
            }
        }
    });
    
    // 键盘快捷键
    document.addEventListener('keydown', function(e) {
        // Ctrl + Enter 提交登录表单
        if (e.ctrlKey && e.key === 'Enter') {
            const loginForm = document.getElementById('loginFormSubmit');
            if (loginForm) {
                loginForm.requestSubmit();
            }
        }
        
        // ESC 关闭所有模态框
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal-overlay.active');
            modals.forEach(modal => {
                modal.classList.remove('active');
            });
        }
        
        // F1 显示帮助
        if (e.key === 'F1') {
            e.preventDefault();
            alert('菜籽游极客社交平台\n\n快捷键：\n• Ctrl+Enter: 提交表单\n• ESC: 关闭模态框\n• F1: 显示帮助\n\n测试账户：\n用户名: admin\n密码: Admin@123456');
        }
    });
    
    // 页面加载进度
    window.addEventListener('load', function() {
        // 移除加载状态
        const loginBox = document.getElementById('loginBox');
        if (loginBox) {
            // 初始状态不需要loading，但保留class以便提交时触发边框旋转
            loginBox.classList.remove('loading');
        }
        
        // 显示欢迎消息
        setTimeout(() => {
            console.log('%c🚀 菜籽游极客社交平台已加载完成！', 'color: #45f3ff; font-size: 16px; font-weight: bold;');
            console.log('%c🔧 版本: 1.0.0 | 环境: 生产环境', 'color: #2ecc71; font-size: 12px;');
            console.log('%c⚠️ 仅供授权访问，未经许可请勿尝试破解。', 'color: #ff2770; font-size: 10px;');
        }, 1000);
    });
    
    // 错误处理
    window.addEventListener('error', function(e) {
        console.error('页面错误:', e.error);
        
        // 显示友好的错误消息
        const loginError = document.getElementById('loginError');
        if (loginError) {
            loginError.textContent = '系统出现错误，请刷新页面重试。';
            loginError.classList.add('show');
        }
    });
    
    // 离线检测
    window.addEventListener('offline', function() {
        const loginError = document.getElementById('loginError');
        if (loginError) {
            loginError.textContent = '网络连接已断开，请检查网络设置。';
            loginError.classList.add('show');
        }
    });
    
    window.addEventListener('online', function() {
        const loginError = document.getElementById('loginError');
        if (loginError) {
            loginError.textContent = '网络连接已恢复。';
            loginError.classList.add('show');
            
            setTimeout(() => {
                loginError.classList.remove('show');
            }, 3000);
        }
    });
    </script>
    
    <footer style="text-align:center;padding:16px 20px;background:#eeeef0;border-top:1px solid rgba(60,60,67,0.06);color:#7c7c82;font-size:13px;">
        &copy; 2026 菜籽游 &middot; 纵流 | 开放 &middot; 聚合 &middot; 创造
    </footer>
</body>
</html>