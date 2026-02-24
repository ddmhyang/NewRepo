<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$post_id = intval($_POST['id'] ?? 0);
$table_name = $_POST['table_name'] ?? 'gallery';

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 게시물 ID입니다.']);
    exit;
}

$allowed_tables = ['gallery', 'a_gallery', 'b_gallery', 'c_gallery', 'd_gallery', 'e_gallery', 'chan_gallery'];
if (!in_array($table_name, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => '잘못된 게시판 접근입니다.']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '게시물 삭제에 실패했습니다.']);
}
$stmt->close();
?>