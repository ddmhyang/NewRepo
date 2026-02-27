<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$content = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => '내용을 입력해주세요.']);
    exit;
}

$is_admin_flag = $is_admin ? 1 : 0; // 현재 글 쓰는 사람이 관리자인지 체크

$stmt = $mysqli->prepare("INSERT INTO guestbook (content, is_admin) VALUES (?, ?)");
$stmt->bind_param("si", $content, $is_admin_flag);

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