<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}
$slug = $_POST['slug'] ?? '';
$content = $_POST['content'] ?? '';

$stmt = $mysqli->prepare("INSERT INTO pages (slug, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = ?");
$stmt->bind_param("sss", $slug, $content, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '저장 실패']);
}
$stmt->close();
?>