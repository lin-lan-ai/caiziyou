<?php
/**
 * 菜籽游官网 - 首页
 */

require_once __DIR__ . '/../includes/config.php';

// 检查维护模式
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
$stmt->execute();
$result = $stmt->get_result();
$maintenance = $result->fetch_assoc();
$stmt->close();

if ($maintenance && $maintenance['setting_value'] === 'true' && getCurrentUserRole() !== 'admin') {
    include __DIR__ . '/maintenance.php';
    exit;
}

// 获取网站设置
$settings = [];
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_name', 'site_description', 'contact_email')");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();

// 获取热门游戏
$games = [];
$stmt = $conn->prepare("
    SELECT g.id, g.title, g.slug, g.short_description, g.cover_image, g.rating, g.download_count, c.name as category_name
    FROM games g
    LEFT JOIN game_categories c ON g.category_id = c.id
    WHERE g.is_active = TRUE AND g.is_featured = TRUE
    ORDER BY g.rating DESC, g.download_count DESC
    LIMIT 6
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}
$stmt->close();

// 获取最新文章
$articles = [];
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.slug, a.excerpt, a.cover_image, a.published_at, u.username as author_name
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.is_published = TRUE
    ORDER BY a.published_at DESC
    LIMIT 4
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}
$stmt->close();

// 获取游戏分类
$categories = [];
$stmt = $conn->prepare("
    SELECT id, name, slug, icon
    FROM game_categories
    WHERE is_active = TRUE
    ORDER BY sort_order
    LIMIT 8
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

$isLoggedIn = isLoggedIn();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? '菜籽游官网'); ?> - 专业的游戏平台</title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description'] ?? '专业的游戏平台，提供最新游戏资讯、下载和社区交流'); ?>">
    <meta name="keywords" content="游戏,下载,资讯,社区,菜籽游">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($settings['site_name'] ?? '菜籽游官网'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($settings['site_description'] ?? '专业的游戏平台，提供最新游戏资讯、下载和社区交流'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme Toggle Script -->
    <script>
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    </script>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="/" class="logo">
                    <i class="fas fa-gamepad"></i>
                    <span><?php echo htmlspecialchars($settings['site_name'] ?? '菜籽游'); ?></span>
                </a>
            </div>
            
            <div class="navbar-menu">
                <a href="/" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>首页</span>
                </a>
                <a href="/games.php" class="nav-link">
                    <i class="fas fa-gamepad"></i>
                    <span>游戏库</span>
                </a>
                <a href="/news.php" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    <span>资讯</span>
                </a>
                <a href="/community.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>社区</span>
                </a>
                <a href="/download.php" class="nav-link">
                    <i class="fas fa-download"></i>
                    <span>下载</span>
                </a>
                <a href="/about.php" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    <span>关于</span>
                </a>
            </div>
            
            <div class="navbar-actions">
                <button class="btn-icon" id="theme-toggle" aria-label="切换主题">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="search-box">
                    <input type="text" placeholder="搜索游戏..." id="search-input">
                    <button class="btn-icon" id="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <?php if ($isLoggedIn): ?>
                    <div class="user-dropdown">
                        <button class="user-avatar">
                            <img src="<?php echo $_SESSION['user_avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="用户头像">
                            <span><?php echo htmlspecialchars($_SESSION['username'] ?? '用户'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="/dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>控制面板</span>
                            </a>
                            <a href="/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>个人资料</span>
                            </a>
                            <a href="/library.php" class="dropdown-item">
                                <i class="fas fa-book"></i>
                                <span>我的游戏库</span>
                            </a>
                            <?php if ($userRole === 'admin' || $userRole === 'moderator'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="/admin/" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>管理面板</span>
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>退出登录</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="/login.php" class="btn btn-outline">登录</a>
                        <a href="/register.php" class="btn btn-primary">注册</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <button class="navbar-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- 移动端菜单 -->
    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <span>菜单</span>
            <button class="btn-icon" id="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-menu-content">
            <a href="/" class="mobile-menu-item active">
                <i class="fas fa-home"></i>
                <span>首页</span>
            </a>
            <a href="/games.php" class="mobile-menu-item">
                <i class="fas fa-gamepad"></i>
                <span>游戏库</span>
            </a>
            <a href="/news.php" class="mobile-menu-item">
                <i class="fas fa-newspaper"></i>
                <span>资讯</span>
            </a>
            <a href="/community.php" class="mobile-menu-item">
                <i class="fas fa-users"></i>
                <span>社区</span>
            </a>
            <a href="/download.php" class="mobile-menu-item">
                <i class="fas fa-download"></i>
                <span>下载</span>
            </a>
            <a href="/about.php" class="mobile-menu-item">
                <i class="fas fa-info-circle"></i>
                <span>关于</span>
            </a>
            
            <?php if ($isLoggedIn): ?>
                <div class="mobile-menu-divider"></div>
                <a href="/dashboard.php" class="mobile-menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>控制面板</span>
                </a>
                <a href="/profile.php" class="mobile-menu-item">
                    <i class="fas fa-user"></i>
                    <span>个人资料</span>
                </a>
                <a href="/library.php" class="mobile-menu-item">
                    <i class="fas fa-book"></i>
                    <span>我的游戏库</span>
                </a>
                <a href="/logout.php" class="mobile-menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>退出登录</span>
                </a>
            <?php else: ?>
                <div class="mobile-menu-divider"></div>
                <a href="/login.php" class="mobile-menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>登录</span>
                </a>
                <a href="/register.php" class="mobile-menu-item">
                    <i class="fas fa-user-plus"></i>
                    <span>注册</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 主要内容 -->
    <main>
        <!-- 英雄区域 -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">发现精彩游戏世界</h1>
                    <p class="hero-description">探索数千款精品游戏，获取最新资讯，加入活跃社区</p>
                    <div class="hero-actions">
                        <a href="/games.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-gamepad"></i>
                            <span>浏览游戏</span>
                        </a>
                        <a href="/register.php" class="btn btn-outline btn-lg">
                            <i class="fas fa-user-plus"></i>
                            <span>免费加入</span>
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="/assets/images/hero-games.png" alt="游戏展示">
                </div>
            </div>
        </section>

        <!-- 游戏分类 -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">游戏分类</h2>
                    <a href="/categories.php" class="section-link">
                        <span>查看全部</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <a href="/category/<?php echo htmlspecialchars($category['slug']); ?>" class="category-card">
                            <div class="category-icon">
                                <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-gamepad'); ?>"></i>
                            </div>
                            <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 热门游戏 -->
        <section class="section bg-light">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">热门游戏</h2>
                    <a href="/games.php?sort=popular" class="section-link">
                        <span>更多热门</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="games-grid">
                    <?php foreach ($games as $game): ?>
                        <div class="game-card">
                            <div class="game-card-header">
                                <img src="<?php echo htmlspecialchars($game['cover_image'] ?? '/assets/images/game-placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($game['title']); ?>" 
                                     class="game-cover">
                                <div class="game-badge">热门</div>
                            </div>
                            <div class="game-card-body">
                                <div class="game-category"><?php echo htmlspecialchars($game['category_name'] ?? '未分类'); ?></div>
                                <h3 class="game-title"><?php echo htmlspecialchars($game['title']); ?></h3>
                                <p class="game-description"><?php echo htmlspecialchars($game['short_description'] ?? '暂无描述'); ?></p>
                                <div class="game-stats">
                                    <div class="game-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($game['rating'], 1); ?></span>
                                    </div>
                                    <div class="game-downloads">
                                        <i class="fas fa-download"></i>
                                        <span><?php echo number_format($game['download_count']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="game-card-footer">
                                <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-eye"></i>
                                    <span>查看详情</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 最新资讯 -->
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">最新资讯</h2>
                    <a href="/news.php" class="section-link">
                        <span>更多资讯</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <div class="article-image">
                                <img src="<?php echo htmlspecialchars($article['cover_image'] ?? '/assets/images/article-placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($article['title']); ?>">
                            </div>
                            <div class="article-content">
                                <div class="article-meta">
                                    <span class="article-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('Y-m-d', strtotime($article['published_at'])); ?>
                                    </span>
                                    <span class="article-author">
                                        <i class="far fa-user"></i>
                                        <?php echo htmlspecialchars($article['author_name'] ?? '匿名'); ?>
                                    </span>
                                </div>
                                <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt'] ?? '暂无摘要'); ?></p>
                                <a href="/article/<?php echo htmlspecialchars($article['slug']); ?>" class="article-link">
                                    <span>阅读全文</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 特色功能 -->
        <section class="section bg-dark text-light">
            <div class="container">
                <h2 class="section-title text-center">为什么选择菜籽游？</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <h3 class="feature-title">海量游戏</h3>
                        <p class="feature-description">收录数千款精品游戏                        <p class="feature-description">收录数千款精品游戏，涵盖各种类型和平台</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="feature-title">高速下载</h3>
                        <p class="feature-description">多节点CDN加速，享受极速下载体验</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">安全可靠</h3>
                        <p class="feature-description">所有游戏经过严格检测，确保安全无毒</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="feature-title">活跃社区</h3>
                        <p class="feature-description">与数百万玩家交流心得，分享游戏乐趣</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 下载统计 -->
        <section class="section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" data-count="10000">0</div>
                        <div class="stat-label">游戏数量</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="5000000">0</div>
                        <div class="stat-label">注册用户</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="25000000">0</div>
                        <div class="stat-label">总下载量</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="100000">0</div>
                        <div class="stat-label">日活跃用户</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-gamepad"></i>
                        <span><?php echo htmlspecialchars($settings['site_name'] ?? '菜籽游'); ?></span>
                    </div>
                    <p class="footer-description">
                        专业的游戏平台，提供最新游戏资讯、下载和社区交流
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="微博">
                            <i class="fab fa-weibo"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="微信">
                            <i class="fab fa-weixin"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="QQ">
                            <i class="fab fa-qq"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">快速链接</h3>
                    <ul class="footer-links">
                        <li><a href="/">首页</a></li>
                        <li><a href="/games.php">游戏库</a></li>
                        <li><a href="/news.php">最新资讯</a></li>
                        <li><a href="/download.php">客户端下载</a></li>
                        <li><a href="/about.php">关于我们</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">支持帮助</h3>
                    <ul class="footer-links">
                        <li><a href="/help.php">帮助中心</a></li>
                        <li><a href="/faq.php">常见问题</a></li>
                        <li><a href="/contact.php">联系我们</a></li>
                        <li><a href="/privacy.php">隐私政策</a></li>
                        <li><a href="/terms.php">服务条款</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="footer-title">联系我们</h3>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($settings['contact_email'] ?? 'contact@cziyo.club'); ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>400-123-4567</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>北京市朝阳区游戏产业园</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? '菜籽游'); ?>. 保留所有权利.
                </div>
                <div class="footer-links-bottom">
                    <a href="/sitemap.php">网站地图</a>
                    <a href="/privacy.php">隐私政策</a>
                    <a href="/terms.php">服务条款</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script>
        // 数字动画
        function animateNumbers() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const increment = target / 200;
                let current = 0;
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.floor(current).toLocaleString();
                        setTimeout(updateCounter, 1);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                };
                
                updateCounter();
            });
        }

        // 主题切换
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = themeToggle.querySelector('i');
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            themeIcon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        });

        // 移动端菜单
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.add('active');
        });
        
        mobileMenuClose.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
        });

        // 搜索功能
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        
        searchBtn.addEventListener('click', () => {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
            }
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });

        // 用户下拉菜单
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            const userAvatar = userDropdown.querySelector('.user-avatar');
            const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
            
            userAvatar.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // 点击其他地方关闭下拉菜单
            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('show');
            });
        }

        // 滚动动画
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                    
                    if (entry.target.classList.contains('stat-number')) {
                        animateNumbers();
                        observer.unobserve(entry.target);
                    }
                }
            });
        }, observerOptions);

        // 观察需要动画的元素
        document.querySelectorAll('.game-card, .article-card, .feature-card, .stat-card').forEach(el => {
            observer.observe(el);
        });

        // 初始化
        document.addEventListener('DOMContentLoaded', () => {
            // 设置当前年份
            document.querySelectorAll('.current-year').forEach(el => {
                el.textContent = new Date().getFullYear();
            });
            
            // 初始化工具提示
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', (e) => {
                    const text = e.target.getAttribute('data-tooltip');
                    const tooltipEl = document.createElement('div');
                    tooltipEl.className = 'tooltip';
                    tooltipEl.textContent = text;
                    document.body.appendChild(tooltipEl);
                    
                    const rect = e.target.getBoundingClientRect();
                    tooltipEl.style.left = `${rect.left + rect.width / 2}px`;
                    tooltipEl.style.top = `${rect.top - tooltipEl.offsetHeight - 10}px`;
                    
                    e.target._tooltip = tooltipEl;
                });
                
                tooltip.addEventListener('mouseleave', (e) => {
                    if (e.target._tooltip) {
                        e.target._tooltip.remove();
                        delete e.target._tooltip;
                    }
                });
            });
        });
    </script>
</body>
</html>