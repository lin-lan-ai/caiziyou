-- 菜籽游x纵流社群平台 - 数据库初始化脚本
-- 创建时间: 2026-04-21

-- 创建数据库
CREATE DATABASE IF NOT EXISTS caiziyou_community 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE caiziyou_community;

-- 用户表（基于原有设计扩展）
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT '/assets/images/default-avatar.png',
    bio TEXT,
    contact_info JSON, -- 存储联系方式（QQ、微信等）
    unique_id VARCHAR(20) UNIQUE, -- 唯一ID，如：CZ0001
    role ENUM('visitor', 'user', 'member', 'admin') DEFAULT 'visitor',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_unique_id (unique_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 社群（团）表
CREATE TABLE IF NOT EXISTS communities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    purpose TEXT, -- 建团目的
    creator_id INT NOT NULL,
    cover_images JSON, -- 封面轮换图数组
    qq_group VARCHAR(20), -- QQ群号
    wechat_qr_code VARCHAR(255), -- 微信群二维码图片URL
    is_public BOOLEAN DEFAULT TRUE, -- 是否公开
    member_count INT DEFAULT 0,
    status ENUM('active', 'pending', 'closed', 'banned') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_creator_id (creator_id),
    INDEX idx_status (status),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 社群成果表（图片/视频）
CREATE TABLE IF NOT EXISTS community_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT NOT NULL,
    title VARCHAR(200),
    description TEXT,
    media_type ENUM('image', 'video') DEFAULT 'image',
    media_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    INDEX idx_community_id (community_id),
    INDEX idx_is_public (is_public),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 社群成员表
CREATE TABLE IF NOT EXISTS community_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('creator', 'admin', 'member') DEFAULT 'member',
    join_status ENUM('pending', 'approved', 'rejected', 'banned') DEFAULT 'pending',
    joined_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_community_user (community_id, user_id),
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_community_id (community_id),
    INDEX idx_user_id (user_id),
    INDEX idx_join_status (join_status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 私聊会话表
CREATE TABLE IF NOT EXISTS private_chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_pair (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user1_id (user1_id),
    INDEX idx_user2_id (user2_id),
    INDEX idx_last_message_at (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 私聊消息表
CREATE TABLE IF NOT EXISTS private_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type ENUM('text', 'image', 'video', 'file') DEFAULT 'text',
    content TEXT,
    media_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES private_chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_id (chat_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户设置表
CREATE TABLE IF NOT EXISTS user_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 系统通知表
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('join_request', 'message', 'system', 'community') DEFAULT 'system',
    title VARCHAR(200),
    content TEXT,
    related_id INT, -- 关联的ID（如社群ID、消息ID等）
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户活动日志表
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    activity_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建默认用户（管理员）
INSERT INTO users (username, email, password_hash, nickname, unique_id, role) 
VALUES (
    'admin', 
    'admin@cziyo.club', 
    '$2y$12$7Q8B9C0D1E2F3G4H5I6J7K8L9M0N1O2P3Q4R5S6T7U8V9W0X1Y2Z3A4B5C6D', 
    '系统管理员', 
    'CZ0001', 
    'admin'
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 创建测试用户
INSERT INTO users (username, email, password_hash, nickname, unique_id, role) 
VALUES (
    'testuser', 
    'test@cziyo.club', 
    '$2y$12$T1U2V3W4X5Y6Z7A8B9C0D1E2F3G4H5I6J7K8L9M0N1O2P3Q4R5S6T7U8V9W', 
    '测试用户', 
    'CZ0002', 
    'user'
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 创建测试社群
INSERT INTO communities (name, slug, description, purpose, creator_id, cover_images, qq_group, wechat_qr_code, status) 
VALUES (
    '菜籽游官方社群',
    'official',
    '菜籽游官方认证社群，欢迎各位玩家加入交流',
    '为菜籽游玩家提供官方交流平台，分享游戏心得，组织游戏活动',
    1,
    '["/assets/images/community-cover1.jpg", "/assets/images/community-cover2.jpg"]',
    '123456789',
    '/assets/images/wechat-qr-official.png',
    'active'
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

INSERT INTO communities (name, slug, description, purpose, creator_id, cover_images, qq_group, status) 
VALUES (
    '游戏开发交流',
    'game-dev',
    '游戏开发者交流社群，分享开发经验和技术',
    '聚集游戏开发者，交流开发技术，分享项目经验',
    2,
    '["/assets/images/community-cover3.jpg"]',
    '987654321',
    'active'
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- 添加社群成员
INSERT IGNORE INTO community_members (community_id, user_id, role, join_status, joined_at) 
VALUES 
(1, 1, 'creator', 'approved', NOW()),
(1, 2, 'member', 'approved', NOW()),
(2, 2, 'creator', 'approved', NOW());

-- 更新社群成员数量
UPDATE communities c 
SET member_count = (
    SELECT COUNT(*) 
    FROM community_members cm 
    WHERE cm.community_id = c.id AND cm.join_status = 'approved'
);

-- 创建数据库用户并授权
CREATE USER IF NOT EXISTS 'caiziyou_community'@'localhost' IDENTIFIED BY 'Community@2026';
GRANT ALL PRIVILEGES ON caiziyou_community.* TO 'caiziyou_community'@'localhost';
FLUSH PRIVILEGES;

-- 创建系统设置表
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认系统设置
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', '菜籽游x纵流社群', 'string', '网站名称'),
('site_description', '点对点私聊+独立社群展示平台', 'string', '网站描述'),
('registration_enabled', 'true', 'boolean', '是否开放注册'),
('community_creation_enabled', 'true', 'boolean', '是否允许创建社群'),
('default_user_role', 'user', 'string', '默认用户角色'),
('max_communities_per_user', '5', 'integer', '每个用户最多创建的社群数'),
('max_cover_images', '5', 'integer', '每个社群最多封面图数量'),
('max_achievements', '20', 'integer', '每个社群最多成果数量'),
('message_retention_days', '365', 'integer', '消息保留天数'),
('theme_color', '#667eea', 'string', '主题颜色'),
('secondary_color', '#764ba2', 'string', '次要颜色'),
('accent_color', '#10b981', 'string', '强调颜色')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;