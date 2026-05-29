<?php
$file = $_GET['f'] ?? '';
$allowed = ['pptx','pdf'];
$path = __DIR__ . '/downloads/' . $file;
if (file_exists($path)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) { http_response_code(403); exit; }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-cache');
    readfile($path);
} else {
    http_response_code(404);
    echo 'File not found';
}
