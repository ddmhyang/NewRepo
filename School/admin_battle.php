<?php
// admin_battle.php : 전투 방 관리 (강제 종료 기능)
require_once 'common.php';

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// 방 삭제 로직
if (isset($_POST['action']) && $_POST['action'] === 'delete_room') {
    $rid = to_int($_POST['room_id']);
    sql_exec("DELETE FROM School_Battles WHERE room_id=?", [$rid]);
    echo "<script>alert('방이 삭제되었습니다.'); location.replace('admin_battle.php');</script>";
    exit;
}

// 진행 중인 방 조회
$rooms = sql_fetch_all("
    SELECT b.*, m.name as host_name 
    FROM School_Battles b 
    JOIN School_Members m ON b.host_id = m.id 
    ORDER BY b.room_id DESC
");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>전투 방 관리</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <style>
        body { font-family: 'Pretendard'; padding: 20px; background:#F0F2F5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f9f9f9; }
        .status-fighting { color: green; font-weight: bold; }
        .status-ended { color: gray; }
        .btn-del { background: red; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h2>⚔️ 전투 방 관리</h2>
    <button onclick="location.href='index.php'" style="margin-bottom:10px;">메인으로</button>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>방장(유저)</th>
                <th>상태</th>
                <th>생성시간</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rooms as $r): ?>
            <tr>
                <td><?=$r['room_id']?></td>
                <td><?=$r['host_name']?></td>
                <td class="status-<?=strtolower($r['status'])?>"><?=$r['status']?></td>
                <td><?=$r['created_at']?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('정말 삭제합니까? 유저는 튕기게 됩니다.');">
                        <input type="hidden" name="action" value="delete_room">
                        <input type="hidden" name="room_id" value="<?=$r['room_id']?>">
                        <button type="submit" class="btn-del">강제삭제</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$rooms) echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>진행 중인 전투가 없습니다.</td></tr>"; ?>
        </tbody>
    </table>
</div>
</body>
</html>