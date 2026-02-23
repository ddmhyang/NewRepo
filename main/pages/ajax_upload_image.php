<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => '업로드된 파일이 없습니다.']);
    exit;
}

$file = $_FILES['file'];
$uploadDir = '../uploads/gallery/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$newFileName = uniqid('img-') . '.' . $ext;
$targetPath = $uploadDir . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $url = '/uploads/gallery/' . $newFileName;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
}
?>