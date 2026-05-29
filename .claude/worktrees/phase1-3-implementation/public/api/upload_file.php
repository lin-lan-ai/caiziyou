<?php
require_once __DIR__ . '/../../includes/community_config.php';
if (!isCommunityLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success'=>false,'error'=>'not logged in']));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success'=>false,'error'=>'method not allowed']));
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['success'=>false,'error'=>'upload failed', 'code'=>$_FILES['file']['error'] ?? -1]));
}

$uploadDir = __DIR__ . '/../downloads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$originalName = $_FILES['file']['name'];
// Sanitize filename: keep ASCII alphanumeric, dots, dashes, underscores only
$safeName = preg_replace('/[^\w\.\-]/', '_', $originalName);
// Avoid overwriting by appending number
$destPath = $uploadDir . '/' . $safeName;
$counter = 1;
while (file_exists($destPath)) {
    $info = pathinfo($safeName);
    $destPath = $uploadDir . '/' . $info['filename'] . '_' . $counter . '.' . ($info['extension'] ?? '');
    $counter++;
}

if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
    echo json_encode([
        'success' => true,
        'file' => basename($destPath),
        'url' => '/downloads/' . rawurlencode(basename($destPath))
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'move failed']);
}
