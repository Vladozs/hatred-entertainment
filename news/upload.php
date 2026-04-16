<?php
// news/upload.php
session_start();
// Проверка авторизации: только после входа
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

if (empty($_FILES['file'])) {
    echo json_encode(['success'=>false,'error'=>'No file']);
    exit;
}

$file = $_FILES['file'];
$allowed = ['jpg','jpeg','png','webp','gif'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false,'error'=>'Unsupported file type']);
    exit;
}

$fn = bin2hex(random_bytes(8)) . '.' . $ext;
$dest = $uploadsDir . '/' . $fn;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success'=>false,'error'=>'Upload failed']);
    exit;
}

$url = '/news/uploads/' . $fn;
echo json_encode(['success'=>true,'url'=>$url]);
exit;
