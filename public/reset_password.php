<?php
/**
 * 菜籽游x纵流社群 - 重置密码页面
 * 通过令牌验证后，允许用户设置新密码
 */
require_once __DIR__ . '/../includes/community_config.php';

// 如果已经登录，重定向到首页
if (isCommunityLoggedIn()) {
    communityRedirect('/index_app.php');
}

$siteName = getSystemSetting('site_name', '菜籽游x纵流社群');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f5f5f7;
            --card-bg: #ffffff;
            --accent: #007aff;
            --accent-hover: #0066d6;
            --text: #1d1d1f;
            --text-dim: #86868b;
            --text-muted: #aeaeb2;
            --border: #e5e5ea;
            --input-bg: #f5f5f7;
            --success-bg: #e8f8e8;
            --success-text: #1a7d1a;
            --error-bg: #fde8e8;
            --error-text: #c41e1e;
            --info-bg: #e8f0fe;
            --info-text: #1a5fb4;
            --font: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif;
            --radius-card: 20px;
            --radius-input: 12px;
            --radius-msg: 10px;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font: 16px/1.6 var(--font);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-card);
            padding: 40px 36px;
            width: 420px;
            max-width: 100%;
            box-shadow: var(--shadow);
            text-align: center;
            animation: fadeUp 0.4s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card .icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(0, 122, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .card .icon-wrap i {
            font-size: 24px;
            color: var(--accent);
        }

        .card h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }

        .card .desc {
            color: var(--text-dim);
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* 加载状态 */
        .status-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
            min-height: 160px;
        }

        .status-indicator .spinner-large {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .status-indicator .status-text {
            color: var(--text-dim);
            font-size: 15px;
        }

        /* 状态消息（非表单内） */
        .status-message {
            padding: 16px;
            border-radius: var(--radius-msg);
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
            animation: fadeSlide 0.25s ease;
        }

        .status-message.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .status-message.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .status-message.info {
            background: var(--info-bg);
            color: var(--info-text);
        }

        .status-message a {
            color: inherit;
            font-weight: 600;
            text-decoration: underline;
        }

        /* 表单 */
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-input);
            font-size: 16px;
            font-family: var(--font);
            outline: none;
            transition: border-color 0.2s, background 0.2s;
            background: var(--input-bg);
            color: var(--text);
            -webkit-appearance: none;
        }

        .form-group input:focus {
            border-color: var(--accent);
            background: var(--card-bg);
        }

        .form-group input.error {
            border-color: var(--error-text);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius-input);
            font-size: 16px;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 4px;
        }

        .btn:hover { opacity: 0.92; }
        .btn:active { transform: scale(0.98); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn.loading .btn-label { display: none; }
        .btn.loading .spinner { display: inline-block; }

        /* 表单内的消息 */
        .msg-box {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: var(--radius-msg);
            font-size: 14px;
            display: none;
            text-align: left;
            line-height: 1.5;
            animation: fadeSlide 0.25s ease;
        }

        .msg-box.show { display: block; }
        .msg-box.success { background: var(--success-bg); color: var(--success-text); }
        .msg-box.error { background: var(--error-bg); color: var(--error-text); }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bottom-links {
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-dim);
        }

        .bottom-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .bottom-links a:hover { text-decoration: underline; }

        .hidden { display: none !important; }

        @media (max-width: 480px) {
            .card { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="fas fa-unlock-alt"></i>
        </div>
        <h1>重置密码</h1>
        <p class="desc" id="pageDesc">验证重置链接...</p>

        <!-- 加载 & 状态区域 -->
        <div id="statusArea" class="status-indicator">
            <div class="spinner-large"></div>
            <div class="status-text">正在验证链接有效性...</div>
        </div>

        <!-- 状态消息（令牌无效等） -->
        <div id="statusMessage" class="status-message hidden"></div>

        <!-- 密码重置表单 -->
        <div id="formArea" class="hidden">
            <p class="desc" style="font-size:13px;margin-bottom:20px;color:var(--text-dim);">
                请输入你的新密码，长度至少 6 位
            </p>
            <form id="resetForm" novalidate>
                <div class="form-group">
                    <label for="newPassword">新密码</label>
                    <input type="password" id="newPassword" placeholder="至少 6 位" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">确认新密码</label>
                    <input type="password" id="confirmPassword" placeholder="再次输入新密码" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn" id="submitBtn">
                    <span class="btn-label"><i class="fas fa-save"></i> 设置新密码</span>
                    <span class="spinner"></span>
                </button>
            </form>
            <div class="msg-box" id="formMsgBox">
                <div id="formMsgContent"></div>
            </div>
        </div>

        <div class="bottom-links" id="bottomLinks">
            <a href="login.php"><i class="fas fa-arrow-left"></i> 返回登录</a>
            <span id="forgotLink" class="hidden" style="margin-left:16px;">&middot; <a href="forgot_password.php">重新申请</a></span>
        </div>
    </div>

    <script>
    (function() {
        var token = new URLSearchParams(window.location.search).get('token');

        var statusArea = document.getElementById('statusArea');
        var statusMessage = document.getElementById('statusMessage');
        var pageDesc = document.getElementById('pageDesc');
        var formArea = document.getElementById('formArea');
        var resetForm = document.getElementById('resetForm');
        var newPwd = document.getElementById('newPassword');
        var confirmPwd = document.getElementById('confirmPassword');
        var submitBtn = document.getElementById('submitBtn');
        var formMsgBox = document.getElementById('formMsgBox');
        var formMsgContent = document.getElementById('formMsgContent');
        var forgotLink = document.getElementById('forgotLink');
        var bottomLinks = document.getElementById('bottomLinks');

        function showStatus(type, html, showForgotLink) {
            statusArea.classList.add('hidden');
            statusMessage.className = 'status-message ' + type + ' hidden';
            void statusMessage.offsetWidth; // force reflow for animation
            statusMessage.className = 'status-message ' + type;
            statusMessage.innerHTML = html;
            statusMessage.classList.remove('hidden');

            if (showForgotLink) {
                forgotLink.classList.remove('hidden');
            }
        }

        function showFormMsg(type, html) {
            formMsgBox.className = 'msg-box show ' + type;
            formMsgContent.innerHTML = html;
        }

        function hideFormMsg() {
            formMsgBox.className = 'msg-box';
            formMsgContent.innerHTML = '';
        }

        function showForm(username) {
            statusArea.classList.add('hidden');
            statusMessage.classList.add('hidden');
            formArea.classList.remove('hidden');
            pageDesc.textContent = '用户：' + username;
            // Focus password input after form is shown
            setTimeout(function() { newPwd.focus(); }, 100);
        }

        // ---- Step 1: Validate token ----
        if (!token) {
            showStatus('error', '缺少重置令牌。请通过<a href="forgot_password.php">忘记密码</a>重新申请。', true);
            pageDesc.textContent = '无效的链接';
        } else {
            fetch('/api/auth/reset-info?token=' + encodeURIComponent(token))
                .then(function(res) {
                    return res.json().then(function(data) {
                        return { status: res.status, data: data };
                    });
                })
                .then(function(result) {
                    var d = result.data;

                    if (d.success && d.valid) {
                        showForm(d.username || '验证通过');
                    } else if (d.error) {
                        var msg = d.error;
                        if (msg === 'Token expired') msg = '重置链接已过期。';
                        else if (msg === 'Invalid token') msg = '重置链接无效。';
                        showStatus('error', msg + ' 请通过<a href="forgot_password.php">忘记密码</a>重新申请。', true);
                        pageDesc.textContent = '链接不可用';
                    } else {
                        showStatus('error', '重置链接无效或已过期。请通过<a href="forgot_password.php">忘记密码</a>重新申请。', true);
                        pageDesc.textContent = '链接不可用';
                    }
                })
                .catch(function() {
                    showStatus('error', '验证请求失败，请检查网络后<a href="javascript:location.reload()">重试</a>。', false);
                    pageDesc.textContent = '验证失败';
                });
        }

        // ---- Step 2: Submit new password ----
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var pwd = newPwd.value;
            var confirm = confirmPwd.value;

            // Client-side validation
            if (!pwd || pwd.length < 6) {
                newPwd.classList.add('error');
                showFormMsg('error', '密码长度至少为 6 位');
                newPwd.focus();
                return;
            }
            newPwd.classList.remove('error');

            if (pwd !== confirm) {
                confirmPwd.classList.add('error');
                showFormMsg('error', '两次输入的密码不一致');
                confirmPwd.focus();
                return;
            }
            confirmPwd.classList.remove('error');

            hideFormMsg();
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token, new_password: pwd })
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, data: data };
                });
            })
            .then(function(result) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');

                var d = result.data;

                if (d.error) {
                    showFormMsg('error', d.error);
                    return;
                }

                if (d.success) {
                    // Hide form, show success
                    formArea.classList.add('hidden');
                    showStatus('success', '<strong>密码已重置成功！</strong><br>请使用新密码登录。', false);
                    pageDesc.textContent = '密码已更新';

                    // Auto-redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = '/login.php';
                    }, 3000);
                } else {
                    showFormMsg('error', '重置失败，请稍后重试。');
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                showFormMsg('error', '网络错误，请检查连接后重试。');
            });
        });

        // Input handlers to clear error states
        newPwd.addEventListener('input', function() { this.classList.remove('error'); });
        confirmPwd.addEventListener('input', function() { this.classList.remove('error'); });

        // Enter key support
        resetForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.target === newPwd || e.target === confirmPwd)) {
                e.preventDefault();
                resetForm.requestSubmit();
            }
        });
    })();
    </script>

    <footer style="text-align:center;padding:16px 20px;background:#eeeef0;border-top:1px solid rgba(60,60,67,0.06);color:#7c7c82;font-size:13px;position:fixed;bottom:0;left:0;right:0;">
        &copy; 2026 菜籽游 &middot; 纵流 | 开放 &middot; 聚合 &middot; 创造
    </footer>
</body>
</html>
