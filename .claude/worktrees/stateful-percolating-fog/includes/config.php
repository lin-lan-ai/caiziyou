<?php
/**
 * 菜籽游官网 - 数据库配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'caiziyou_user');
define('DB_PASS', 'CaiziYou@2026');
define('DB_NAME', 'caiziyou_db');

// 网站配置
define('SITE_NAME', '菜籽游官网');
define('SITE_URL', 'https://cziyo.club');
define('SITE_TIMEZONE', 'Asia/Shanghai');

// 安全配置
define('SESSION_TIMEOUT', 3600); // 会话超时时间（秒）
define('MAX_LOGIN_ATTEMPTS', 5); // 最大登录尝试次数
define('LOCKOUT_TIME', 900); // 锁定时间（秒）

// 错误报告设置（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set(SITE_TIMEZONE);

// 自动加载类
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 创建数据库连接
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("数据库连接失败: " . $conn->connect_error);
            }
            
            // 设置字符集
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            // 记录错误日志
            error_log("数据库错误: " . $e->getMessage());
            
            // 显示用户友好错误
            die("系统维护中，请稍后再试。");
        }
    }
    
    return $conn;
}

// 安全函数：防止SQL注入
function sanitizeInput($input, $conn = null) {
    if ($conn === null) {
        $conn = getDBConnection();
    }
    
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $conn->real_escape_string($input);
}

// 生成CSRF令牌
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// 密码哈希函数
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 重定向函数
function redirect($url) {
    header("Location: $url");
    exit();
}

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// 获取当前用户ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 获取当前用户角色
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

// (活动记录功能已删除)

// 发送JSON响应
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// 初始化会话
session_start();

// 更新会话时间
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 防止会话固定攻击
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}
?>