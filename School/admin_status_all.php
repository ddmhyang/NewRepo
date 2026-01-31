<?php
// admin_status_all.php : 전체 유저 상태이상 통합 관리
require_once 'common.php';
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// 수정 로직
if (isset($_POST['action']) && $_POST['action'] === 'update_stage') {
    $id = to_int($_POST['active_id']);
    $stage = to_int($_POST['stage']);
    if ($stage <= 0) {
        // 0이면 치료(삭제)
        sql_exec("DELETE FROM School_Status_Active WHERE id=?", [$id]);
    } else {
        // 단계 변경
        sql_exec("UPDATE School_Status_Active SET current_stage=? WHERE id=?", [$stage, $id]);
    }
    echo "<script>history.back();</script>";
    exit;
}

// 전체 조회 (이름 오름차순)
$list = sql_fetch_all("
    SELECT act.*, m.name as user_name, s.name as status_name, s.max_stage
    FROM School_Status_Active act
    JOIN School_Members m ON act.target_id = m.id
    JOIN School_Status_Info s ON act.status_id = s.status_id
    ORDER BY m.name ASC
");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상태이상 통합 관리</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Pretendard'; padding: 20px; background:#F0F2F5; margin:0; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { margin: 0; color: #CE5961; }
        button { cursor: pointer; padding: 8px 12px; border: none; border-radius: 6px; background: #eee; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; }
        th { background: #f9f9f9; color: #666; }
        select { padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-update { background: #AED1D5; color: #333; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-virus"></i> 전체 감염 현황</h2>
        <button onclick="location.href='index.php'">메인으로</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>유저명</th>
                <th>상태이상</th>
                <th>현재단계</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($list as $row): ?>
            <tr>
                <td style="font-weight:bold;"><?=$row['user_name']?></td>
                <td><?=$row['status_name']?></td>
                <form method="POST">
                    <input type="hidden" name="action" value="update_stage">
                    <input type="hidden" name="active_id" value="<?=$row['id']?>">
                    <td>
                        <select name="stage">
                            <option value="0">치료 (제거)</option>
                            <?php for($i=1; $i<=$row['max_stage']; $i++): ?>
                                <option value="<?=$i?>" <?=$i==$row['current_stage']?'selected':''?>><?=$i?>단계</option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td><button type="submit" class="btn-update">적용</button></td>
                </form>
            </tr>
            <?php endforeach; ?>
            <?php if(!$list): ?>
                <tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">현재 감염된 캐릭터가 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>