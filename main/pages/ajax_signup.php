<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $response['message'] = '아이디와 비밀번호를 모두 입력해주세요.';
    echo json_encode($response);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['message'] = '이미 사용 중인 아이디입니다.';
} else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';
    $insert_stmt = $mysqli->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($insert_stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['message'] = '가입 처리 중 오류가 발생했습니다.';
    }
    $insert_stmt->close();
}
$stmt->close();

echo json_encode($response);
?>