-- ============================================================================
-- 菜籽游 - 数据库统一迁移参考
-- Phase 1b: 将 caiziyou_db 合并至 caiziyou_community_db
-- ============================================================================
-- 说明：
--   原始代码包含两套数据库配置：
--     1. caiziyou_db         — 主站数据库（已弃用）
--     2. caiziyou_community_db — 社群平台数据库（当前统一使用）
--
--   Phase 1b 已将全部 PHP 前端页面的数据库连接指向 caiziyou_community_db。
--   以下 SQL 记录了 caiziyou_db 的表结构作为归档参考。
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 已弃用：caiziyou_db 的表结构（存档参考）
-- 此数据库不再被代码引用，保留仅供数据迁移参考。
-- ---------------------------------------------------------------------------

-- 用户表（旧）
CREATE TABLE IF NOT EXISTS `users_old` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 如果 caiziyou_db 中仍有需要保留的数据，
-- 请执行以下迁移（示例）：
--
-- INSERT IGNORE INTO caiziyou_community_db.users (username, email, password_hash, created_at)
-- SELECT u.username, u.email, u.password, u.created_at
-- FROM caiziyou_db.users_old u;

-- ============================================================================
-- 当前使用：caiziyou_community_db 核心表
-- （供快速参考，非迁移脚本）
-- ============================================================================

-- 用户表（当前）
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `nickname` varchar(50) DEFAULT NULL,
    `full_name` varchar(100) DEFAULT NULL,
    `avatar_url` varchar(500) DEFAULT NULL,
    `profile_bg` varchar(500) DEFAULT NULL,
    `bio` text DEFAULT NULL,
    `contact_info` text DEFAULT NULL,
    `friend_code` varchar(20) DEFAULT NULL,
    `unique_id` varchar(20) DEFAULT NULL,
    `role` enum('user','member','admin','moderator') NOT NULL DEFAULT 'user',
    `status` enum('active','disabled','banned') NOT NULL DEFAULT 'active',
    `registration_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` int(11) DEFAULT NULL,
    `reviewed_at` datetime DEFAULT NULL,
    `review_note` text DEFAULT NULL,
    `user_note` text DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `friend_code` (`friend_code`),
    UNIQUE KEY `unique_id` (`unique_id`),
    KEY `status` (`status`),
    KEY `registration_status` (`registration_status`),
    KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 好友申请表
CREATE TABLE IF NOT EXISTS `friend_requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `from_user_id` int(11) NOT NULL,
    `to_user_id` int(11) NOT NULL,
    `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `from_user_id` (`from_user_id`),
    KEY `to_user_id` (`to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 私聊会话表
CREATE TABLE IF NOT EXISTS `private_chats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user1_id` int(11) NOT NULL,
    `user2_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_pair` (`user1_id`,`user2_id`),
    KEY `user2_id` (`user2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 私聊消息表
CREATE TABLE IF NOT EXISTS `private_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `chat_id` int(11) NOT NULL,
    `sender_id` int(11) NOT NULL,
    `content` text NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `chat_id` (`chat_id`),
    KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 团表
CREATE TABLE IF NOT EXISTS `communities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `category` varchar(50) DEFAULT '其他',
    `site_url` varchar(500) DEFAULT NULL,
    `avatar_url` varchar(500) DEFAULT NULL,
    `banners` text DEFAULT NULL,
    `unique_id` varchar(20) DEFAULT NULL,
    `creator_id` int(11) NOT NULL,
    `member_count` int(11) NOT NULL DEFAULT 0,
    `join_type` enum('auto','review') NOT NULL DEFAULT 'auto',
    `post_type` enum('all','admin') NOT NULL DEFAULT 'all',
    `is_public` tinyint(1) NOT NULL DEFAULT 1,
    `status` enum('pending','approved','rejected','archived') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `creator_id` (`creator_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 团成员表
CREATE TABLE IF NOT EXISTS `community_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `community_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `role` enum('creator','admin','member') NOT NULL DEFAULT 'member',
    `join_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `community_id` (`community_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 通知表
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(50) NOT NULL,
    `title` varchar(200) NOT NULL,
    `content` text DEFAULT NULL,
    `related_user_id` int(11) DEFAULT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `read_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 系统设置表
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户会话表（记住我功能）
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `session_token` varchar(255) NOT NULL,
    `expires_at` datetime NOT NULL,
    `last_used` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户资料扩展表
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `bio` text DEFAULT NULL,
    `contact_info` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 操作日志表
CREATE TABLE IF NOT EXISTS `operation_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `username` varchar(100) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `target_type` varchar(50) DEFAULT NULL,
    `target_id` int(11) DEFAULT NULL,
    `detail` text DEFAULT NULL,
    `ip` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
