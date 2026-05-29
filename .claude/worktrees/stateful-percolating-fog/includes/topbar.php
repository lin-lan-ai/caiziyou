<?php
/**
 * 统一顶部栏组件
 * 根据登录状态显示不同的用户入口
 */
?>
<div class="top-bar">
    <div class="top-bar-left">
        <div class="site-logo">
            <i class="fas fa-code"></i>
            <span>菜籽游</span>
        </div>
    </div>
    
    <div class="top-bar-right">
        <?php if (isCommunityLoggedIn()): ?>
            <?php $currentUser = getCurrentCommunityUser(); ?>
            <?php if (!$currentUser): ?>
                <div style="color:#888;font-size:13px;">会话异常</div>
            <?php else: ?>
            <!-- 已登录状态：显示用户头像和菜单 -->
            <div class="user-avatar" id="userAvatar">
                <img src="<?php echo htmlspecialchars($currentUser['avatar_url'] ?? '/assets/images/default-avatar.png'); ?>" 
                     alt="<?php echo htmlspecialchars($currentUser['nickname'] ?? $currentUser['username']); ?>"
                     title="<?php echo htmlspecialchars($currentUser['nickname'] ?? $currentUser['username']); ?>">
                <div class="avatar-menu" id="avatarMenu">
                    <div class="avatar-menu-header">
                        <div class="avatar-menu-avatar">
                            <img src="<?php echo htmlspecialchars($currentUser['avatar_url'] ?? '/assets/images/default-avatar.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($currentUser['nickname']); ?>">
                        </div>
                        <div class="avatar-menu-info">
                            <div class="avatar-menu-name"><?php echo htmlspecialchars($currentUser['nickname'] ?? $currentUser['username']); ?></div>
                            <div class="avatar-menu-username">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
                        </div>
                    </div>
                    <div class="avatar-menu-divider"></div>
                    <a href="/profile.php" class="avatar-menu-item">
                        <i class="fas fa-user"></i>
                        <span>个人资料</span>
                    </a>
                    <a href="/settings.php" class="avatar-menu-item">
                        <i class="fas fa-cog"></i>
                        <span>账户设置</span>
                    </a>
                    <div class="avatar-menu-divider"></div>
                    <a href="/api/logout.php" class="avatar-menu-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>退出登录</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- 未登录状态：显示登录按钮 -->
            <div class="auth-buttons">
                <a href="/login.php" class="auth-button login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>登录</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 顶部栏样式 - 与外部geek.css配合使用 */
.top-bar {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    height: 60px !important;
    padding: 0 20px !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e5e5ea !important;
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 1000;
}

.top-bar-left {
    display: flex;
    align-items: center;
}

.site-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--accent);
    font-size: 1.4rem;
    font-weight: 700;
    text-decoration: none;
}

.site-logo i {
    font-size: 1.8rem;
}

.top-bar-right {
    display: flex;
    align-items: center;
}

/* 用户头像样式 - 重写外部冲突 */
.user-avatar {
    position: relative !important;
    cursor: pointer !important;
    width: auto !important;
    height: auto !important;
    border: none !important;
    box-shadow: none !important;
    overflow: visible !important;
    border-radius: 0 !important;
}

.user-avatar img {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    border: 2px solid var(--accent) !important;
    object-fit: cover !important;
    transition: all 0.3s ease;
    display: block;
}

.user-avatar:hover img {
    border-color: var(--accent-light) !important;
    box-shadow: 0 2px 12px rgba(91,140,255,0.12);
    transform: scale(1.05);
}

/* 头像菜单样式 - 重写外部冲突 */
.avatar-menu {
    position: absolute !important;
    top: 50px !important;
    right: 0 !important;
    width: 280px !important;
    background: #ffffff !important;
    border: 1px solid #e5e5ea !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 24px rgba(var(--black-rgb),0.06) !important;
    display: none !important;
    z-index: 1001 !important;
}

.avatar-menu.active {
    display: block !important;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.avatar-menu-header {
    display: flex;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid rgba(var(--border-rgb),0.06);
}

.avatar-menu-avatar {
    margin-right: 12px;
}

.avatar-menu-avatar img {
    width: 50px !important;
    height: 50px !important;
    border-radius: 50% !important;
    border: 2px solid var(--accent) !important;
}

.avatar-menu-info {
    flex: 1;
}

.avatar-menu-name {
    color: var(--text-primary);
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 2px;
}

.avatar-menu-username {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.avatar-menu-divider {
    height: 1px;
    background: #e5e5ea;
    margin: 8px 0;
}

.avatar-menu-item {
    display: flex !important;
    align-items: center !important;
    padding: 12px 16px !important;
    color: #1d1d1f !important;
    text-decoration: none !important;
    transition: all 0.2s ease;
    cursor: pointer !important;
    gap: 10px !important;
}

.avatar-menu-item:hover {
    background: #f5f5f7 !important;
    color: var(--accent) !important;
}

.avatar-menu-item i {
    width: 20px !important;
    text-align: center !important;
    font-size: 1rem !important;
}

.avatar-menu-item.logout-item {
    color: var(--danger) !important;
}

.avatar-menu-item.logout-item:hover {
    background: rgba(255,59,48,0.08) !important;
}

/* 登录按钮 */
.auth-buttons {
    display: flex;
    gap: 12px;
}

.auth-button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border: 1px solid;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.auth-button.login-button {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(91,140,255,0.06);
}

.auth-button.login-button:hover {
    background: rgba(91,140,255,0.12);
    box-shadow: 0 2px 8px rgba(91,140,255,0.1);
}
</style>

<script>
// 头像菜单交互 - 使用原生onclick确保100%绑定
(function(){
    var ua = document.getElementById('userAvatar');
    var am = document.getElementById('avatarMenu');
    if (!ua || !am) return;
    
    ua.onclick = function(e) {
        if (e) e.stopPropagation();
        am.classList.toggle('active');
    };
    
    document.onclick = function() {
        am.classList.remove('active');
    };
    
    am.onclick = function(e) {
        if (e) e.stopPropagation();
    };
    
    // 登出确认
    var logouts = document.querySelectorAll('.logout-item');
    for (var i = 0; i < logouts.length; i++) {
        logouts[i].onclick = function(e) {
            if (!confirm('确定要退出登录吗？')) {
                if (e) e.preventDefault();
            }
        };
    }
})();
</script>