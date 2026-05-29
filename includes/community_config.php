<?php
/**
 * 菜籽游x纵流社群平台 - 配置文件
 */

// .env 加载函数（仅在未定义时加载一次）
if (!function_exists('loadEnv')) {
    function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (strpos($trimmed, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}
loadEnv();

// 数据库配置（从环境变量读取，带默认值回退）
define('COMMUNITY_DB_HOST', getenv('COMMUNITY_DB_HOST') ?: 'localhost');
define('COMMUNITY_DB_USER', getenv('COMMUNITY_DB_USER') ?: 'caiziyou_community');
define('COMMUNITY_DB_PASS', getenv('COMMUNITY_DB_PASS') ?: 'Community@2026');
define('COMMUNITY_DB_NAME', getenv('COMMUNITY_DB_NAME') ?: 'caiziyou_community_db');

// 网站配置
define('COMMUNITY_SITE_NAME', '菜籽游x纵流社群');
define('COMMUNITY_SITE_URL', 'https://cziyo.club');
define('COMMUNITY_SITE_DESCRIPTION', '点对点私聊+独立社群展示平台');
define('COMMUNITY_TIMEZONE', 'Asia/Shanghai');

// 权限常量
define('ROLE_VISITOR', 'visitor');
define('ROLE_USER', 'user');
define('ROLE_MEMBER', 'member');
define('ROLE_ADMIN', 'admin');

// 功能开关
define('REGISTRATION_ENABLED', true);
define('COMMUNITY_CREATION_ENABLED', true);
define('PRIVATE_CHAT_ENABLED', true);

// 限制设置
define('MAX_COMMUNITIES_PER_USER', 5);
define('MAX_COVER_IMAGES', 5);
define('MAX_ACHIEVEMENTS', 20);
define('MESSAGE_RETENTION_DAYS', 365);

// 路径配置
define('UPLOAD_PATH', '/var/www/caiziyou/public/uploads/');
define('UPLOAD_URL', '/uploads/');
define('AVATAR_PATH', UPLOAD_PATH . 'avatars/');
define('AVATAR_URL', UPLOAD_URL . 'avatars/');
define('COVER_PATH', UPLOAD_PATH . 'covers/');
define('COVER_URL', UPLOAD_URL . 'covers/');
define('ACHIEVEMENT_PATH', UPLOAD_PATH . 'achievements/');
define('ACHIEVEMENT_URL', UPLOAD_URL . 'achievements/');

// 安全配置
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);
define('CSRF_TOKEN_LIFETIME', 1800);

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 设置时区
date_default_timezone_set(COMMUNITY_TIMEZONE);

// 自动加载类
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 创建数据库连接
function getCommunityDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(
                COMMUNITY_DB_HOST, 
                COMMUNITY_DB_USER, 
                COMMUNITY_DB_PASS, 
                COMMUNITY_DB_NAME
            );
            
            if ($conn->connect_error) {
                throw new Exception("数据库连接失败: " . $conn->connect_error);
            }
            
            // 设置字符集
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("社群数据库错误: " . $e->getMessage());
            die("系统维护中，请稍后再试。");
        }
    }
    
    return $conn;
}

// 获取系统设置
function getSystemSetting($key, $default = null) {
    $conn = getCommunityDBConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? $row['setting_value'] : $default;
}

// 安全函数：防止SQL注入
function sanitizeCommunityInput($input, $conn = null) {
    if ($conn === null) {
        $conn = getCommunityDBConnection();
    }
    
    if (is_array($input)) {
        return array_map('sanitizeCommunityInput', $input);
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $conn->real_escape_string($input);
}

// 生成CSRF令牌
function generateCommunityCSRFToken() {
    if (!isset($_SESSION['community_csrf_token'])) {
        $_SESSION['community_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['community_csrf_token_time'] = time();
    }
    return $_SESSION['community_csrf_token'];
}

// 验证CSRF令牌
function validateCommunityCSRFToken($token) {
    if (!isset($_SESSION['community_csrf_token']) || 
        !isset($_SESSION['community_csrf_token_time']) ||
        $token !== $_SESSION['community_csrf_token']) {
        return false;
    }
    
    // 检查令牌是否过期
    if (time() - $_SESSION['community_csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['community_csrf_token']);
        unset($_SESSION['community_csrf_token_time']);
        return false;
    }
    
    return true;
}

// 密码哈希函数
function hashCommunityPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// 验证密码
function verifyCommunityPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 重定向函数
function communityRedirect($url) {
    // 确保URL以斜杠开头
    if (strpos($url, '/') !== 0) {
        $url = '/' . $url;
    }
    // 使用相对路径重定向，避免绝对URL可能引起的问题
    header("Location: $url");
    exit();
}

// 检查用户是否登录
function isCommunityLoggedIn() {
    return isset($_SESSION['community_user_id']) && isset($_SESSION['community_user_role']);
}

// 获取当前用户ID
function getCurrentCommunityUserId() {
    return $_SESSION['community_user_id'] ?? null;
}

// 获取当前用户角色
function getCurrentCommunityUserRole() {
    return $_SESSION['community_user_role'] ?? ROLE_VISITOR;
}

// 检查用户权限
function hasCommunityPermission($requiredRole) {
    $currentRole = getCurrentCommunityUserRole();
    
    $roleHierarchy = [
        ROLE_VISITOR => 0,
        ROLE_USER => 1,
        ROLE_MEMBER => 2,
        ROLE_ADMIN => 3
    ];
    
    return isset($roleHierarchy[$currentRole]) && 
           $roleHierarchy[$currentRole] >= $roleHierarchy[$requiredRole];
}

// 获取当前用户信息
function getCurrentCommunityUser() {
    static $user = null;
    
    if ($user === null && isCommunityLoggedIn()) {
        $conn = getCommunityDBConnection();
        $stmt = $conn->prepare("
            SELECT id, username, email, nickname, avatar_url, bio, contact_info, unique_id, role 
            FROM users 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $_SESSION['community_user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
    
    return $user;
}

/**
 * 账户日志写入函数 (PHP端)
 * 写入 /var/log/caiziyou/accounts/{user_id}/operation.log
 * 与 Python account_logger.py 共享同一日志目录
 */
function logCommunityActivity($userId, $action, $detail = '') {
    $logDir = '/var/log/caiziyou/accounts/' . intval($userId);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/operation.log';
    $record = json_encode([
        'time' => date('Y-m-d H:i:s'),
        'action' => $action,
        'detail' => $detail ?: $action,
        'category' => 'operation',
    ], JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($logFile, $record, FILE_APPEND | LOCK_EX);
}

// 发送JSON响应
function sendCommunityJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// 生成唯一ID
function generateUniqueId($prefix = 'CZ') {
    $conn = getCommunityDBConnection();
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(unique_id, 3) AS UNSIGNED)) as max_num FROM users WHERE unique_id LIKE ?");
    $likePattern = $prefix . '%';
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $nextNum = ($row['max_num'] ?? 0) + 1;
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// 检查用户是否可以创建社群
function canUserCreateCommunity($userId) {
    if (!COMMUNITY_CREATION_ENABLED) {
        return false;
    }
    
    $conn = getCommunityDBConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM communities 
        WHERE creator_id = ? AND status IN ('active', 'pending')
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] < MAX_COMMUNITIES_PER_USER;
}

// 获取用户未读消息数
function getUserUnreadMessageCount($userId) {
    $conn = getCommunityDBConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM private_messages pm
        JOIN private_chats pc ON pm.chat_id = pc.id
        WHERE (pc.user1_id = ? OR pc.user2_id = ?) 
          AND pm.sender_id != ? 
          AND pm.is_read = FALSE
    ");
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// 获取用户未读通知数
function getUserUnreadNotificationCount($userId) {
    $conn = getCommunityDBConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = FALSE
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// 初始化会话
session_start();

// 更新会话时间
if (isset($_SESSION['community_last_activity']) && 
    (time() - $_SESSION['community_last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['community_last_activity'] = time();

// 防止会话固定攻击
if (!isset($_SESSION['community_created'])) {
    $_SESSION['community_created'] = time();
} else if (time() - $_SESSION['community_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['community_created'] = time();
}

// 自动登录检查（记住我功能）
if (!isCommunityLoggedIn() && isset($_COOKIE['community_remember_token'])) {
    $token = $_COOKIE['community_remember_token'];
    $conn = getCommunityDBConnection();
    
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.role 
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        WHERE us.session_token = ? AND us.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $_SESSION['community_user_id'] = $user['id'];
        $_SESSION['community_user_role'] = $user['role'];
        $_SESSION['community_username'] = $user['username'];
        $_SESSION['community_logged_in'] = true;
        $_SESSION['community_login_time'] = time();
        
        // 更新会话时间
        $updateStmt = $conn->prepare("UPDATE user_sessions SET last_used = NOW() WHERE session_token = ?");
        $updateStmt->bind_param("s", $token);
        $updateStmt->execute();
        $updateStmt->close();
        
        logCommunityActivity($user['id'], '自动登录', '记住我自动登录');
    }
    
    // 关闭原始语句
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

// 权限检查中间件函数
function requireCommunityLogin() {
    if (!isCommunityLoggedIn()) {
        $_SESSION['community_redirect_url'] = $_SERVER['REQUEST_URI'];
        communityRedirect('/login.php');
    }
}

function requireCommunityRole($requiredRole) {
    requireCommunityLogin();
    
    if (!hasCommunityPermission($requiredRole)) {
        http_response_code(403);
        die("权限不足，无法访问此页面。");
    }
}

// 获取主题颜色
function getThemeColor($type = 'primary') {
    $colors = [
        'primary' => getSystemSetting('theme_color', '#667eea'),
        'secondary' => getSystemSetting('secondary_color', '#764ba2'),
        'accent' => getSystemSetting('accent_color', '#10b981'),
        'success' => '#10b981',
        'danger' => '#ef4444',
        'warning' => '#f59e0b',
        'info' => '#3b82f6'
    ];
    
    return $colors[$type] ?? $colors['primary'];
}

// 格式化时间
function formatCommunityTime($timestamp, $format = 'Y-m-d H:i') {
    return date($format, strtotime($timestamp));
}

// 获取相对时间
function getRelativeTime($timestamp) {
    $diff = time() - strtotime($timestamp);
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', strtotime($timestamp));
    }
}

// 文件上传处理
function handleCommunityUpload($file, $type = 'avatar') {
    $allowedTypes = [
        'avatar' => ['image/jpeg', 'image/png', 'image/gif'],
        'cover' => ['image/jpeg', 'image/png', 'image/gif'],
        'achievement' => ['image/jpeg', 'image/png', 'image/gif', 'video/mp4']
    ];
    
    $maxSizes = [
        'avatar' => 2 * 1024 * 1024, // 2MB
        'cover' => 5 * 1024 * 1024, // 5MB
        'achievement' => 10 * 1024 * 1024 // 10MB
    ];
    
    $paths = [
        'avatar' => AVATAR_PATH,
        'cover' => COVER_PATH,
        'achievement' => ACHIEVEMENT_PATH
    ];
    
    $urls = [
        'avatar' => AVATAR_URL,
        'cover' => COVER_URL,
        'achievement' => ACHIEVEMENT_URL
    ];
    
    // 检查文件类型
    if (!in_array($file['type'], $allowedTypes[$type])) {
        throw new Exception('不支持的文件类型');
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSizes[$type]) {
        throw new Exception('文件大小超过限制');
    }
    
    // 创建目录
    if (!file_exists($paths[$type])) {
        mkdir($paths[$type], 0755, true);
    }
    
    // 生成文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $paths[$type] . $filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('文件上传失败');
    }
    
    return $urls[$type] . $filename;
}

// 清理旧文件
function cleanupOldFiles($days = 30) {
    $paths = [AVATAR_PATH, COVER_PATH, ACHIEVEMENT_PATH];
    $cutoff = time() - ($days * 24 * 60 * 60);
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            foreach (glob($path . '*') as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                }
            }
        }
    }
}

// 执行定期清理（每天一次）
if (rand(1, 100) === 1) {
    cleanupOldFiles();
}

?>