<?php
/**
 * 验证当前登录用户的密码（用于管理员面板解锁）
 */
require_once __DIR__ . '/../../includes/community_config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// 检查是否已登录
if (!isset($_SESSION['community_user_id'])) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

$userId = $_SESSION['community_user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => '请输入密码']);
    exit;
}

$conn = getCommunityDBConnection();
$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => '用户不存在']);
    exit;
}

if (verifyCommunityPassword($password, $user['password_hash'])) {
    // 生成标准 JWT 令牌（兼容 Python PyJWT 库）
    // 关键：PHP json_encode 默认带空格，而 Python 的 json.dumps 默认不带
    // 必须用 JSON_UNESCAPED_UNICODE + 手动紧凑来保证签名一致
    function jwt_base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_UNESCAPED_UNICODE);
    $payload = json_encode([
        'user_id' => intval($userId),
        'role' => $_SESSION['community_user_role'] ?? 'user',
        'iat' => time(),
        'exp' => time() + 3600
    ], JSON_UNESCAPED_UNICODE);
    
    $header_b64 = jwt_base64url_encode($header);
    $payload_b64 = jwt_base64url_encode($payload);
    $secret = 'caiziyou-secret-key-2026';
    $signature_b64 = jwt_base64url_encode(hash_hmac('sha256', "$header_b64.$payload_b64", $secret, true));
    $token = "$header_b64.$payload_b64.$signature_b64";
    
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    echo json_encode(['success' => false, 'error' => '密码错误']);
}
