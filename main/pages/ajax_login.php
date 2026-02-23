<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $response['message'] = '아이디와 비밀번호를 모두 입력해주세요.';
} else {
    $stmt = $mysqli->prepare("SELECT password_hash FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $response['success'] = true;
        } else {
            $response['message'] = '아이디 또는 비밀번호가 잘못되었습니다.';
        }
    } else {
        $response['message'] = '존재하지 않는 사용자입니다.';
    }
    $stmt->close();
}

echo json_encode($response);
?>