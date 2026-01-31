<?php
include 'common.php';
try {
    sql_exec("ALTER TABLE School_Members ADD COLUMN injury INT DEFAULT 0");
    echo "injury 컬럼 추가 완료";
} catch(Exception $e) {
    echo "이미 있거나 오류: " . $e->getMessage();
}
?>