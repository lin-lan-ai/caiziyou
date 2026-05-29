/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: caiziyou_community_db
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agent_sessions`
--

DROP TABLE IF EXISTS `agent_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(64) NOT NULL COMMENT 'WebSocket session唯一标识',
  `auth_code` varchar(10) NOT NULL COMMENT '授权码(6位数字)',
  `auth_code_expires` datetime NOT NULL COMMENT '授权码过期时间',
  `status` enum('pending','active','expired','closed') NOT NULL DEFAULT 'pending' COMMENT 'pending=待授权 active=已授权',
  `ws_connected` tinyint(1) NOT NULL DEFAULT 0,
  `last_heartbeat` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_auth_code` (`auth_code`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `agent_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分身EXE会话授权表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_sessions`
--

LOCK TABLES `agent_sessions` WRITE;
/*!40000 ALTER TABLE `agent_sessions` DISABLE KEYS */;
INSERT INTO `agent_sessions` VALUES
(7,1,'8bd6db8d16a887656a2a5f205e83e9e0e8fd4ef6f097d4ca9167ffb2edf67d4f','578250','2026-05-01 10:05:07','active',0,'2026-05-01 10:00:07','2026-05-01 10:00:07');
/*!40000 ALTER TABLE `agent_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_token_transactions`
--

DROP TABLE IF EXISTS `agent_token_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_token_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) DEFAULT NULL COMMENT '关联的会话',
  `amount` int(11) NOT NULL COMMENT '扣除数量(正数=消耗)',
  `balance_before` int(11) NOT NULL DEFAULT 0,
  `balance_after` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(50) NOT NULL COMMENT '操作类型: agent_cmd_exec, agent_cmd_fail, admin_adjust',
  `description` text DEFAULT NULL COMMENT '操作描述',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `agent_token_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分身token交易流水表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_token_transactions`
--

LOCK TABLES `agent_token_transactions` WRITE;
/*!40000 ALTER TABLE `agent_token_transactions` DISABLE KEYS */;
INSERT INTO `agent_token_transactions` VALUES
(1,1,'',-11,1000,989,'agent_cmd_exec','分身指令: 查看系统信息','2026-05-01 10:00:44'),
(2,1,NULL,11,989,1000,'refund','分身离线退款','2026-05-01 10:00:44'),
(3,1,'',-11,1000,989,'agent_cmd_exec','分身指令: 列出桌面文件','2026-05-01 10:00:47'),
(4,1,NULL,11,989,1000,'refund','分身离线退款','2026-05-01 10:00:47'),
(5,1,'',-11,1000,989,'agent_cmd_exec','分身指令: 查看信息','2026-05-01 10:01:00'),
(6,1,NULL,11,989,1000,'refund','分身离线退款','2026-05-01 10:01:00');
/*!40000 ALTER TABLE `agent_token_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communities`
--

DROP TABLE IF EXISTS `communities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `communities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT '/assets/images/community-default.jpg',
  `category` varchar(50) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `member_count` int(11) DEFAULT 0,
  `creator_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  KEY `creator_id` (`creator_id`),
  CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communities`
--

LOCK TABLES `communities` WRITE;
/*!40000 ALTER TABLE `communities` DISABLE KEYS */;
/*!40000 ALTER TABLE `communities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_members`
--

DROP TABLE IF EXISTS `community_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','admin','creator') DEFAULT 'member',
  `join_status` enum('pending','approved') DEFAULT 'pending',
  `joined_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community_user` (`community_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `community_members_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`),
  CONSTRAINT `community_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_members`
--

LOCK TABLES `community_members` WRITE;
/*!40000 ALTER TABLE `community_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friend_requests`
--

DROP TABLE IF EXISTS `friend_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `friend_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_request` (`from_user_id`,`to_user_id`),
  KEY `to_user_id` (`to_user_id`),
  CONSTRAINT `friend_requests_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friend_requests_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friend_requests`
--

LOCK TABLES `friend_requests` WRITE;
/*!40000 ALTER TABLE `friend_requests` DISABLE KEYS */;
INSERT INTO `friend_requests` VALUES
(1,1,9,'accepted','2026-04-30 13:14:02','2026-05-01 03:46:26');
/*!40000 ALTER TABLE `friend_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `junius_chat`
--

DROP TABLE IF EXISTS `junius_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `junius_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `reply` text DEFAULT NULL,
  `status` enum('pending','replied') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `junius_chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `junius_chat`
--

LOCK TABLES `junius_chat` WRITE;
/*!40000 ALTER TABLE `junius_chat` DISABLE KEYS */;
INSERT INTO `junius_chat` VALUES
(1,1,'只有好友和消息可以用','收到你的消息: 「只有好友和消息可以用」\n我是 Junius，后续会接入更聪明的 AI 大脑。现在先简单回复你 😊','replied','2026-04-30 12:51:02','2026-04-30 12:51:03'),
(2,1,'额','收到你的消息: 「额」\n我是 Junius，后续会接入更聪明的 AI 大脑。现在先简单回复你 😊','replied','2026-04-30 14:22:58','2026-04-30 14:23:00');
/*!40000 ALTER TABLE `junius_chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT '通知类型: friend_request, system, mention, like, comment',
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `related_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `private_chats`
--

DROP TABLE IF EXISTS `private_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `private_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chat` (`user1_id`,`user2_id`),
  KEY `idx_user1` (`user1_id`),
  KEY `idx_user2` (`user2_id`),
  CONSTRAINT `private_chats_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `private_chats_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `private_chats`
--

LOCK TABLES `private_chats` WRITE;
/*!40000 ALTER TABLE `private_chats` DISABLE KEYS */;
INSERT INTO `private_chats` VALUES
(3,1,9,'2026-05-01 03:46:40','2026-05-01 03:46:40');
/*!40000 ALTER TABLE `private_chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `private_messages`
--

DROP TABLE IF EXISTS `private_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat` (`chat_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_read` (`is_read`,`chat_id`),
  CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `private_chats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `private_messages`
--

LOCK TABLES `private_messages` WRITE;
/*!40000 ALTER TABLE `private_messages` DISABLE KEYS */;
INSERT INTO `private_messages` VALUES
(1,3,1,'？',1,'2026-05-01 03:50:44'),
(2,3,1,'hui fu yi x w',1,'2026-05-01 07:58:31'),
(3,3,1,'你好，测试消息',1,'2026-05-01 08:36:37'),
(4,3,1,'测试',1,'2026-05-01 09:16:04'),
(5,3,1,'测试',1,'2026-05-01 09:17:02');
/*!40000 ALTER TABLE `private_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES
(1,'site_name','菜籽游x纵流社群','2026-04-21 19:14:56'),
(2,'site_description','点对点私聊+独立社群展示平台','2026-04-21 19:14:56'),
(3,'site_version','1.0.0','2026-04-21 19:14:56');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `activity_data` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activities`
--

LOCK TABLES `user_activities` WRITE;
/*!40000 ALTER TABLE `user_activities` DISABLE KEYS */;
INSERT INTO `user_activities` VALUES
(1,1,'auto_login',NULL,'223.155.178.216','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 12:53:34'),
(2,1,'user_login',NULL,'223.155.178.216','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 12:59:48'),
(3,1,'user_login',NULL,'223.155.178.216','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 13:03:45'),
(4,1,'auto_login',NULL,'116.162.132.25','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 13:44:11'),
(5,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 14:22:38'),
(6,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 14:55:25'),
(7,1,'auto_login',NULL,'10.0.0.2','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-04-30 16:34:57'),
(8,1,'auto_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 02:40:52'),
(9,1,'auto_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 03:19:29'),
(10,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 03:43:14'),
(11,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 04:50:52'),
(12,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 05:44:10'),
(13,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 05:44:36'),
(14,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 05:50:38'),
(15,1,'user_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 05:55:28'),
(16,1,'user_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 05:58:49'),
(17,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:03:07'),
(18,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:03:23'),
(19,1,'user_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 06:04:32'),
(20,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:06:26'),
(21,1,'user_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 06:09:32'),
(22,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:15:12'),
(23,1,'user_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 06:27:00'),
(24,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:28:43'),
(25,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:29:45'),
(26,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:30:23'),
(27,1,'user_login',NULL,'154.64.255.112','curl/7.88.1','2026-05-01 06:32:36'),
(28,1,'user_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 06:36:24'),
(29,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:37:22'),
(30,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:37:55'),
(31,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 06:40:28'),
(32,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:04:15'),
(33,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:05:20'),
(34,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:05:52'),
(35,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:06:27'),
(36,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:06:47'),
(37,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:07:55'),
(38,1,'user_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 07:17:41'),
(39,1,'user_login',NULL,'154.64.255.112','curl/7.88.1','2026-05-01 07:18:33'),
(40,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:19:56'),
(41,1,'user_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 07:40:22'),
(42,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 07:44:26'),
(43,1,'user_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 08:15:27'),
(44,1,'auto_login',NULL,'42.49.21.7','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 08:33:25'),
(45,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 08:36:30'),
(46,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:14:20'),
(47,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:14:56'),
(48,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:15:16'),
(49,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:15:30'),
(50,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:15:59'),
(51,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:16:57'),
(52,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:17:30'),
(53,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:21:10'),
(54,1,'user_login',NULL,'116.162.132.118','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-05-01 09:21:57'),
(55,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:23:35'),
(56,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:26:27'),
(57,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:26:34'),
(58,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:27:51'),
(59,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:27:58'),
(60,1,'user_login',NULL,'154.64.255.112','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/147.0.7727.15 Safari/537.36','2026-05-01 09:42:04');
/*!40000 ALTER TABLE `user_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_twitter` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES
(4,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 13:01:54','2026-04-23 13:01:54'),
(5,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 13:30:10','2026-04-23 13:30:10'),
(6,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-23 13:48:14','2026-04-23 13:48:14'),
(8,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-29 14:32:48','2026-04-29 14:32:48'),
(9,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-30 07:23:18','2026-04-30 07:23:18');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_used` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES
(27,7,'5c069847a65d200730c3bbcf13ce4ca49d60d7cc046c7978c69ab80268753864','2026-05-23 21:03:22','2026-04-25 23:07:00','2026-04-23 13:03:22'),
(30,8,'c818af60913b57534171c6ff717799a458dc2b8d3c4e48b6fb08cf8a7084f27d','2026-05-23 21:38:31',NULL,'2026-04-23 13:38:31'),
(32,9,'62123e4572106345b2e3a4760a70ab44d517f6ae07e45c43a37d2c441e7832bc','2026-05-23 21:48:52',NULL,'2026-04-23 13:48:52'),
(46,11,'e8b6282d4e9da930bdcee4a8b5bb594909c06595c4ef3f2a6199ef91752d2e43','2026-05-29 22:32:57',NULL,'2026-04-29 14:32:57'),
(59,12,'60388c671bd29ecb6cd9ddab0310df6ce50a16eb2a262f661999ab67ba9d79d8','2026-05-30 15:23:27',NULL,'2026-04-30 07:23:27'),
(106,1,'05b034fdbaceba9f534e8a767824053bfc46ce082f0f2515ae5ce6e597d2f6b4','2026-05-31 17:42:04',NULL,'2026-05-01 09:42:04');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `friend_code` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT '/assets/images/default-avatar.png',
  `bio` text DEFAULT NULL,
  `user_note` text DEFAULT NULL,
  `registration_status` varchar(20) DEFAULT 'approved',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `token_balance` int(11) NOT NULL DEFAULT 0 COMMENT '纵流分身EXE token余额',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `friend_code` (`friend_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'CZ000001','admin','264541','admin@cziyo.club','$2y$10$puWve6BdyyAH.dOevZl7xOk.SuxIkNv4EJ/rETLFdvBPIUH8HeNye','测试',NULL,'/assets/images/default-avatar.png','Hello',NULL,'approved',NULL,NULL,NULL,NULL,'admin','active','2026-04-21 19:14:56','2026-05-01 17:42:04',1000),
(7,'U17769493146015','yyyy','723034','yaohanyu1@qq.com','$2y$12$K.W.FrbPriiOUImX6lvteuw4bdJv9HcYIP/TfOy/m0Q0CSaD45ATq','yyyy','yyyy','/assets/images/default-avatar.png',NULL,'','approved',1,'2026-04-23 21:02:27','',NULL,'user','active','2026-04-23 21:01:54','2026-04-23 21:03:22',0),
(8,'U17769510105069','54088syq','921549','3678816179@qq.com','$2y$12$Hhz3AafipODnhxE1tCSg5OpI7B0ysjC6EWmyJdLD06jXSzgT2TdAW','无言','无言','/assets/images/default-avatar.png',NULL,'儿子','approved',1,'2026-04-23 21:31:51','',NULL,'user','active','2026-04-23 21:30:10','2026-04-23 21:38:31',0),
(9,'U17769520947353','夜之玄','538642','m18274464110@163.com','$2y$12$hiG3GLqVKuKQKek7Z9LrpudAxuUHivkK7ZJmjLrsWNbGUaErMJhUe','夜之玄','夜之玄','/assets/images/default-avatar.png',NULL,'','approved',1,'2026-04-23 21:49:18','',NULL,'user','active','2026-04-23 21:48:14','2026-04-23 21:48:52',0),
(11,'U17774731683332','292049','728564','1@1.com','$2y$12$g0tmvzgdoMA.461UPVykp.3tbpf/WVkvpBPqNxQxqCm8CRbGT7IB.','292049','292049','/assets/images/default-avatar.png',NULL,'','approved',1,'2026-04-30 17:58:02','',NULL,'user','active','2026-04-29 22:32:48','2026-04-29 22:32:57',0),
(12,'U17775337984412','zeng','126896','706434665@qq.com','$2y$12$p7cTfC36jDEW5kH8XI89NOudlQKH3e/qW8fc0uhFBwotIiFo1OFva','zeng','zeng','/assets/images/default-avatar.png',NULL,'','approved',1,'2026-04-30 17:53:41','',NULL,'user','active','2026-04-30 15:23:18','2026-04-30 15:23:27',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-01 17:50:59
