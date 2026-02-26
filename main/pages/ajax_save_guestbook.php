<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$content = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => '내용을 입력해주세요.']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO guestbook (content) VALUES (?)");

$stmt->bind_param("s", $content);

if ($stmt->execute()) {
    $new_entry_id = $stmt->insert_id;
    $result = $mysqli->query("SELECT * FROM guestbook WHERE id = $new_entry_id");
    $new_entry = $result->fetch_assoc();
    
    $new_entry['content'] = nl2br(htmlspecialchars($new_entry['content']));
    
    echo json_encode(['success' => true, 'entry' => $new_entry]);
} else {
    echo json_encode(['success' => false, 'message' => '저장에 실패했습니다.']);
}

$stmt->close();
?>