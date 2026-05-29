<?php
require_once __DIR__ . '/../includes/community_config.php';

// 获取当前登录用户 ID
$userId = $_SESSION['community_user_id'] ?? null;

if ($userId) {
    // 清除 serverside token（user_sessions 表）
    try {
        $conn = getCommunityDBConnection();
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // 忽略
    }
}

// 清除 session
$_SESSION = [];
session_destroy();
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 86400, '/');
}

header('Location: /login.php');
exit;
