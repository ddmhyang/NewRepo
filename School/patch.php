<?php
// patch.php : DB 컬럼 강제 추가
require_once 'config.php';
$pdo = get_conn();

try {
    // 1. 프로필 이미지 컬럼 추가
    $pdo->exec("ALTER TABLE School_Members ADD COLUMN img_profile VARCHAR(255) DEFAULT NULL");
    echo "✅ img_profile 컬럼 추가 성공<br>";
} catch (Exception $e) {
    echo "ℹ️ img_profile 컬럼이 이미 존재하거나 오류 발생 (무시 가능)<br>";
}

try {
    // 2. 부상 컬럼 추가
    $pdo->exec("ALTER TABLE School_Members ADD COLUMN injury INT DEFAULT 0");
    echo "✅ injury 컬럼 추가 성공<br>";
} catch (Exception $e) {
    echo "ℹ️ injury 컬럼이 이미 존재함<br>";
}

echo "<hr><h3>패치 완료! <a href='inventory.php'>가방으로 돌아가기</a></h3>";
?>