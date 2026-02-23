<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$post_id = intval($_POST['id'] ?? 0);
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 게시물 ID입니다.']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM gallery WHERE id = ?");
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '게시물 삭제에 실패했습니다.']);
}
$stmt->close();
?>