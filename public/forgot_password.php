<?php
/**
 * 菜籽游x纵流社群 - 忘记密码页面
 * 用户提交用户名，系统生成密码重置链接
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
    <title>忘记密码 - <?php echo htmlspecialchars($siteName); ?></title>
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

        @keyframes spin { to { transform: rotate(360deg); } }

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

        .msg-box .reset-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 10px 18px;
            background: var(--success-text);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: opacity 0.2s;
        }

        .msg-box .reset-link-btn:hover { opacity: 0.85; }

        .msg-box .token-hint {
            margin-top: 8px;
            font-size: 12px;
            opacity: 0.7;
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

        @media (max-width: 480px) {
            .card { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="fas fa-key"></i>
        </div>
        <h1>忘记密码</h1>
        <p class="desc">输入你的用户名，系统将生成密码重置链接</p>

        <form id="forgotForm" novalidate>
            <div class="form-group">
                <label for="usernameInput">用户名</label>
                <input type="text" id="usernameInput" placeholder="请输入你的用户名" required autocomplete="username" autofocus>
            </div>
            <button type="submit" class="btn" id="submitBtn">
                <span class="btn-label"><i class="fas fa-paper-plane"></i> 提交请求</span>
                <span class="spinner"></span>
            </button>
        </form>

        <div class="msg-box" id="msgBox">
            <div id="msgContent"></div>
        </div>

        <div class="bottom-links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> 返回登录</a>
        </div>
    </div>

    <script>
    (function() {
        var form = document.getElementById('forgotForm');
        var input = document.getElementById('usernameInput');
        var btn = document.getElementById('submitBtn');
        var msgBox = document.getElementById('msgBox');
        var msgContent = document.getElementById('msgContent');

        function showMsg(type, html) {
            msgBox.className = 'msg-box show ' + type;
            msgContent.innerHTML = html;
        }

        function hideMsg() {
            msgBox.className = 'msg-box';
            msgContent.innerHTML = '';
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var username = input.value.trim();
            if (!username) {
                input.classList.add('error');
                showMsg('error', '请输入用户名');
                input.focus();
                return;
            }
            input.classList.remove('error');
            hideMsg();

            btn.disabled = true;
            btn.classList.add('loading');

            fetch('/api/auth/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: username })
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, data: data };
                });
            })
            .then(function(result) {
                btn.disabled = false;
                btn.classList.remove('loading');

                var d = result.data;

                if (d.error) {
                    showMsg('error', d.error);
                    return;
                }

                if (d.success) {
                    var html = '';

                    if (d.reset_url) {
                        html = '<strong>重置链接已生成</strong><br>';
                        html += '<a href="' + d.reset_url + '" class="reset-link-btn">';
                        html += '<i class="fas fa-external-link-alt"></i> 点击此处重置密码</a>';
                        html += '<div class="token-hint">令牌有效期 24 小时，请尽快使用</div>';
                    } else {
                        html = d.message || '如果该用户名存在，重置链接已生成。请联系管理员审核。';
                    }

                    showMsg('success', html);
                } else {
                    showMsg('error', '请求失败，请稍后重试。');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.classList.remove('loading');
                showMsg('error', '网络错误，请检查连接后重试。');
            });
        });

        input.addEventListener('input', function() {
            this.classList.remove('error');
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.requestSubmit();
            }
        });
    })();
    </script>

    <footer style="text-align:center;padding:16px 20px;background:#eeeef0;border-top:1px solid rgba(60,60,67,0.06);color:#7c7c82;font-size:13px;position:fixed;bottom:0;left:0;right:0;">
        &copy; 2026 菜籽游 &middot; 纵流 | 开放 &middot; 聚合 &middot; 创造
    </footer>
</body>
</html>
