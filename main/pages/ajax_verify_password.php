<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$post_id = intval($_POST['post_id'] ?? 0);
$password = $_POST['password'] ?? '';

if ($post_id <= 0 || empty($password)) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$stmt = $mysqli->prepare("SELECT password_hash FROM gallery WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if ($post && password_verify($password, $post['password_hash'])) {
    $_SESSION['post_access'][$post_id] = time();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '비밀번호가 일치하지 않습니다.']);
}
$stmt->close();
?>