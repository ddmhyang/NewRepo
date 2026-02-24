<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$character_name = $_POST['character_name'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($character_name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => '필수 값이 비어있습니다.']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO chat (character_name, message) VALUES (?, ?)");
$stmt->bind_param("ss", $character_name, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $mysqli->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => '메시지 저장에 실패했습니다.']);
}
$stmt->close();
?>