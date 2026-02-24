<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$current_settings_result = $mysqli->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $current_settings_result->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
$old_char1_name = $current_settings['character1_name'] ?? 'Hyun';
$old_char2_name = $current_settings['character2_name'] ?? 'Chan';

$new_char1_name = $_POST['character1_name'];
$new_char2_name = $_POST['character2_name'];

if ($old_char1_name !== $new_char1_name) {
    $stmt = $mysqli->prepare("UPDATE chat SET character_name = ? WHERE character_name = ?");
    $stmt->bind_param("ss", $new_char1_name, $old_char1_name);
    $stmt->execute();
    $stmt->close();
}
if ($old_char2_name !== $new_char2_name) {
    $stmt = $mysqli->prepare("UPDATE chat SET character_name = ? WHERE character_name = ?");
    $stmt->bind_param("ss", $new_char2_name, $old_char2_name);
    $stmt->execute();
    $stmt->close();
}

function update_setting($key, $value, $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO chan_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
}

update_setting('character1_name', $new_char1_name, $mysqli);
update_setting('character2_name', $new_char2_name, $mysqli);

$uploadDir = '../assets/img/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

if (isset($_FILES['index_button_image']) && $_FILES['index_button_image']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'btn_index_' . uniqid() . '.png';
    if (move_uploaded_file($_FILES['index_button_image']['tmp_name'], $uploadDir . $fileName)) {
        update_setting('index_button_image', '../assets/img/' . $fileName, $mysqli);
    }
}

if (isset($_FILES['main_background']) && $_FILES['main_background']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'bg_main_' . uniqid() . '.png';
    if (move_uploaded_file($_FILES['main_background']['tmp_name'], $uploadDir . $fileName)) {
        update_setting('main_background', '../assets/img/' . $fileName, $mysqli);
    }
}

if (isset($_FILES['chat_background']) && $_FILES['chat_background']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'bg_chat_' . uniqid() . '.png';
    if (move_uploaded_file($_FILES['chat_background']['tmp_name'], $uploadDir . $fileName)) {
        update_setting('chat_background', '../assets/img/' . $fileName, $mysqli);
    }
}

if (isset($_FILES['character1_image']) && $_FILES['character1_image']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'profile1_' . uniqid() . '.png';
    if (move_uploaded_file($_FILES['character1_image']['tmp_name'], $uploadDir . $fileName)) {
        update_setting('character1_image', '/assets/img/' . $fileName, $mysqli);
    }
}
if (isset($_FILES['character2_image']) && $_FILES['character2_image']['error'] === UPLOAD_ERR_OK) {
    $fileName = 'profile2_' . uniqid() . '.png';
    if (move_uploaded_file($_FILES['character2_image']['tmp_name'], $uploadDir . $fileName)) {
        update_setting('character2_image', '/assets/img/' . $fileName, $mysqli);
    }
}

echo json_encode(['success' => true, 'message' => '설정이 저장되었습니다. 페이지를 새로고침합니다.', 'redirect_url' => 'reload']);
?>