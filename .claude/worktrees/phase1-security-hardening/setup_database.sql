-- 菜籽游官网数据库初始化脚本
-- 创建时间: 2026-04-21

-- 创建数据库
CREATE DATABASE IF NOT EXISTS caiziyou_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE caiziyou_db;

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    avatar_url VARCHAR(255) DEFAULT '/assets/images/default-avatar.png',
    role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended', 'banned') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    lockout_until DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建用户资料表
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    birthdate DATE,
    country VARCHAR(50),
    city VARCHAR(50),
    bio TEXT,
    website VARCHAR(255),
    social_facebook VARCHAR(255),
    social_twitter VARCHAR(255),
    social_instagram VARCHAR(255),
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建用户活动日志表
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_activity (activity(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建登录会话表
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建密码重置表
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reset_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_reset_token (reset_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建邮箱验证表
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    verification_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_verification_token (verification_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建系统设置表
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建游戏分类表
CREATE TABLE IF NOT EXISTS game_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建游戏表
CREATE TABLE IF NOT EXISTS games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    category_id INT,
    developer VARCHAR(100),
    publisher VARCHAR(100),
    release_date DATE,
    price DECIMAL(10, 2),
    discount_price DECIMAL(10, 2),
    cover_image VARCHAR(255),
    banner_image VARCHAR(255),
    trailer_url VARCHAR(255),
    website_url VARCHAR(255),
    platform ENUM('pc', 'mobile', 'console', 'web', 'multi') DEFAULT 'pc',
    rating DECIMAL(3, 1) DEFAULT 0,
    total_ratings INT DEFAULT 0,
    download_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(200),
    meta_description VARCHAR(500),
    meta_keywords VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES game_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_category_id (category_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),
    INDEX idx_rating (rating),
    INDEX idx_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建用户游戏收藏表
CREATE TABLE IF NOT EXISTS user_game_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_game (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_game_id (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建新闻文章表
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    excerpt VARCHAR(500),
    content LONGTEXT,
    author_id INT,
    category ENUM('news', 'guide', 'review', 'update', 'event') DEFAULT 'news',
    cover_image VARCHAR(255),
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    published_at DATETIME,
    meta_title VARCHAR(200),
    meta_description VARCHAR(500),
    meta_keywords VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_author_id (author_id),
    INDEX idx_category (category),
    INDEX idx_is_published (is_published),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建评论表
CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    article_id INT,
    game_id INT,
    parent_id INT,
    is_approved BOOLEAN DEFAULT TRUE,
    likes INT DEFAULT 0,
    dislikes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_article_id (article_id),
    INDEX idx_game_id (game_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_is_approved (is_approved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认系统设置
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', '菜籽游官网', 'string', '网站名称'),
('site_description', '专业的游戏平台，提供最新游戏资讯、下载和社区交流', 'string', '网站描述'),
('site_keywords', '游戏,下载,资讯,社区,菜籽游', 'string', '网站关键词'),
('contact_email', 'contact@cziyo.club', 'string', '联系邮箱'),
('support_email', 'support@cziyo.club', 'string', '客服邮箱'),
('default_user_role', 'user', 'string', '默认用户角色'),
('registration_enabled', 'true', 'boolean', '是否开放注册'),
('max_login_attempts', '5', 'integer', '最大登录尝试次数'),
('lockout_duration', '900', 'integer', '账户锁定时间（秒）'),
('session_timeout', '3600', 'integer', '会话超时时间（秒）'),
('items_per_page', '20', 'integer', '每页显示项目数'),
('maintenance_mode', 'false', 'boolean', '维护模式'),
('google_analytics_id', '', 'string', 'Google Analytics ID'),
('recaptcha_site_key', '', 'string', 'reCAPTCHA站点密钥'),
('recaptcha_secret_key', '', 'string', 'reCAPTCHA密钥'),
('smtp_host', '', 'string', 'SMTP服务器地址'),
('smtp_port', '587', 'integer', 'SMTP端口'),
('smtp_username', '', 'string', 'SMTP用户名'),
('smtp_password', '', 'string', 'SMTP密码'),
('smtp_encryption', 'tls', 'string', 'SMTP加密方式')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 插入默认游戏分类
INSERT INTO game_categories (name, slug, description, icon, sort_order) VALUES
('角色扮演', 'rpg', '沉浸式角色扮演游戏', 'fas fa-user-ninja', 1),
('动作冒险', 'action-adventure', '刺激的动作冒险游戏', 'fas fa-running', 2),
('策略游戏', 'strategy', '考验智力的策略游戏', 'fas fa-chess', 3),
('射击游戏', 'shooter', '激烈的射击对战游戏', 'fas fa-crosshairs', 4),
('体育竞技', 'sports', '真实的体育竞技游戏', 'fas fa-football-ball', 5),
('模拟经营', 'simulation', '模拟经营类游戏', 'fas fa-city', 6),
('休闲益智', 'casual', '轻松休闲的益智游戏', 'fas fa-puzzle-piece', 7),
('独立游戏', 'indie', '独立开发者制作的游戏', 'fas fa-gamepad', 8)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 创建数据库用户并授权
CREATE USER IF NOT EXISTS 'caiziyou_user'@'localhost' IDENTIFIED BY 'CaiziYou@2026';
GRANT ALL PRIVILEGES ON caiziyou_db.* TO 'caiziyou_user'@'localhost';
FLUSH PRIVILEGES;

-- 创建管理员用户（密码：Admin@123456）
INSERT INTO users (username, email, password_hash, full_name, role, email_verified) 
VALUES (
    'admin', 
    'admin@cziyo.club', 
    '$2y$10$NscRNEoqyxNVsxQmhgeddueWLX7SWJyFbPelvsTYMaPX4dxgETu7i', 
    '系统管理员', 
    'admin', 
    TRUE
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 创建测试用户（密码：Test@123456）
INSERT INTO users (username, email, password_hash, full_name, email_verified) 
VALUES (
    'testuser', 
    'test@cziyo.club', 
    '$2y$10$xmnH41fnUmKZ3RXVqzBPCeWmkrQ53tri4leP0g5LiuZCM0VdEs/He', 
    '测试用户', 
    TRUE
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;