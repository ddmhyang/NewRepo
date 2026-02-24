<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = $_POST['title'];
$content = $_POST['content'];
$gallery_type = $_POST['gallery_type'];
$is_private = isset($_POST['is_private']) ? 1 : 0;
$password = $_POST['password'] ?? '';
$thumbnail_path = null;

if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['thumbnail'];
    $uploadDir = '../uploads/gallery/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = 'thumb-' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $thumbnail_path = '/uploads/gallery/' . $newFileName;
    }
}

if (empty($thumbnail_path)) {
    preg_match('/<img[^>]+src="([^">]+)"/', $content, $matches);
    if (isset($matches[1])) {
        $thumbnail_path = $matches[1];
    }
}

$password_hash = null;
if ($is_private && !empty($password)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
}

if ($post_id > 0) {
    $sql = "UPDATE gallery SET title=?, content=?, is_private=?";
    $params = [$title, $content, $is_private];
    $types = "ssi";
    if ($thumbnail_path) { $sql .= ", thumbnail=?"; $params[] = $thumbnail_path; $types .= "s"; }
    if ($password_hash) { $sql .= ", password_hash=?"; $params[] = $password_hash; $types .= "s"; }
    $sql .= " WHERE id=?";
    $params[] = $post_id;
    $types .= "i";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $mysqli->prepare("INSERT INTO gallery (gallery_type, title, content, thumbnail, is_private, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $gallery_type, $title, $content, $thumbnail_path, $is_private, $password_hash);
}

if ($stmt->execute()) {
    $new_id = $post_id > 0 ? $post_id : $mysqli->insert_id;
    echo json_encode(['success' => true, 'redirect_url' => "#/gallery_view?id=" . $new_id]);
} else {
    echo json_encode(['success' => false, 'message' => '저장 실패: ' . $stmt->error]);
}
$stmt->close();
?>