<?php
// admin_member.php : 캐릭터 관리 (일괄 작업 포함 최종본)
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [로직] 일괄 처리 (체크박스)
// ---------------------------------------------------------
if (isset($_POST['bulk_action'])) {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        echo "<script>alert('선택된 캐릭터가 없습니다.'); history.back();</script>";
        exit;
    }
    
    $act = $_POST['bulk_action'];
    $id_list = implode(',', array_map('intval', $ids)); // SQL Injection 방지용 정수 변환

    // A. 일괄 포인트 지급
    if ($act === 'give_point') {
        $amount = to_int($_POST['bulk_point']);
        if ($amount != 0) {
            sql_exec("UPDATE School_Members SET point = point + ? WHERE id IN ($id_list)", [$amount]);
            echo "<script>alert('총 ".count($ids)."명에게 포인트를 지급했습니다.');</script>";
        }
    }
    // B. 일괄 아이템 지급
    elseif ($act === 'give_item') {
        $item_id = to_int($_POST['bulk_item_id']);
        $count = to_int($_POST['bulk_item_count']);
        if ($item_id && $count) {
            foreach($ids as $mid) {
                // 이미 가지고 있는지 확인
                $exist = sql_fetch("SELECT id FROM School_Inventory WHERE owner_id=? AND item_id=?", [$mid, $item_id]);
                if($exist) {
                    sql_exec("UPDATE School_Inventory SET count = count + ? WHERE id=?", [$count, $exist['id']]);
                } else {
                    sql_exec("INSERT INTO School_Inventory (owner_id, item_id, count) VALUES (?, ?, ?)", [$mid, $item_id, $count]);
                }
            }
            echo "<script>alert('일괄 아이템 지급 완료.');</script>";
        }
    }
    // C. 일괄 상태이상 부여
    elseif ($act === 'give_status') {
        $status_id = to_int($_POST['bulk_status_id']);
        if ($status_id) {
            foreach($ids as $mid) {
                // 중복 감염 방지
                $exist = sql_fetch("SELECT id FROM School_Status_Active WHERE target_id=? AND status_id=?", [$mid, $status_id]);
                if(!$exist) {
                    sql_exec("INSERT INTO School_Status_Active (target_id, status_id) VALUES (?, ?)", [$mid, $status_id]);
                }
            }
            echo "<script>alert('일괄 감염 완료.');</script>";
        }
    }
    echo "<script>location.replace('admin_member.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [로직] 개별 생성 및 삭제
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name']);
    $pw = trim($_POST['pw']);
    
    if ($name && $pw) {
        $exists = sql_fetch("SELECT id FROM School_Members WHERE name = ?", [$name]);
        if ($exists) {
            echo "<script>alert('이미 존재하는 이름입니다.');</script>";
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            sql_exec("INSERT INTO School_Members (user_id, pw, name, role) VALUES (?, ?, ?, 'user')", [$name, $hash, $name]);
            echo "<script>alert('캐릭터가 생성되었습니다.'); location.replace('admin_member.php');</script>";
            exit;
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $target_id = to_int($_POST['target_id']);
    if ($target_id > 0) {
        $target = sql_fetch("SELECT role FROM School_Members WHERE id = ?", [$target_id]);
        if ($target['role'] === 'admin') {
            echo "<script>alert('관리자 계정은 삭제할 수 없습니다.');</script>";
        } else {
            sql_exec("DELETE FROM School_Members WHERE id = ?", [$target_id]);
            echo "<script>alert('삭제되었습니다.'); location.replace('admin_member.php');</script>";
            exit;
        }
    }
}

// 목록 조회
$members = sql_fetch_all("SELECT * FROM School_Members ORDER BY role ASC, id DESC");
$items = sql_fetch_all("SELECT * FROM School_Item_Info ORDER BY name ASC");
$status_list = sql_fetch_all("SELECT * FROM School_Status_Info ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>캐릭터 관리 - School RPG</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --secondary: #D67F85; --bg: #F0F2F5; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        h2 { margin-top: 0; color: #333; display: flex; align-items: center; gap: 10px; }
        .back-btn { background: #eee; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-size: 14px; }
        
        /* 생성 폼 */
        .create-box { background: #f9f9f9; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .create-box input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; flex: 1; }
        
        /* 일괄 처리 바 */
        .bulk-bar { background: #e8ecef; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; font-size: 14px; }
        .bulk-group { display: flex; gap: 5px; align-items: center; padding-right: 15px; border-right: 1px solid #ccc; }
        .bulk-group:last-child { border: none; }
        .bulk-group select, .bulk-group input { padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        
        button { cursor: pointer; border: none; border-radius: 6px; font-weight: bold; padding: 10px 15px; color: white; }
        .btn-create { background: var(--primary); }
        .btn-p { background: #3498db; } .btn-i { background: #e67e22; } .btn-s { background: #9b59b6; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; background: #f8f8f8; color: #555; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge.admin { background: #333; color: white; }
        .badge.user { background: #eef; color: #55a; }
        
        .btn-edit { background: var(--secondary); padding: 5px 10px; font-size: 13px; }
        .btn-del { background: #eee; color: #777; padding: 5px 10px; font-size: 13px; }
        .btn-del:hover { background: #ffdede; color: #d00; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fa-solid fa-users-gear" style="color: var(--primary)"></i> 캐릭터 관리</h2>
        <button class="back-btn" onclick="location.href='index.php'" style="background:#eee; color:#333;">돌아가기</button>
    </div>

    <form method="POST" class="create-box">
        <input type="hidden" name="action" value="create">
        <b>새 캐릭터:</b>
        <input type="text" name="name" placeholder="이름 (ID)" required>
        <input type="text" name="pw" placeholder="비밀번호" required>
        <button type="submit" class="btn-create">생성</button>
    </form>

    <form method="POST">
        <div class="bulk-bar">
            <b style="color:#555;">[선택 작업]</b>
            
            <div class="bulk-group">
                <input type="number" name="bulk_point" placeholder="Point" style="width:80px;">
                <button type="submit" name="bulk_action" value="give_point" class="btn-p">포인트 지급</button>
            </div>
            
            <div class="bulk-group">
                <select name="bulk_item_id">
                    <option value="">아이템 선택</option>
                    <?php foreach($items as $i) echo "<option value='{$i['item_id']}'>{$i['name']}</option>"; ?>
                </select>
                <input type="number" name="bulk_item_count" value="1" style="width:50px;">
                <button type="submit" name="bulk_action" value="give_item" class="btn-i">지급</button>
            </div>

            <div class="bulk-group">
                <select name="bulk_status_id">
                    <option value="">상태이상 선택</option>
                    <?php foreach($status_list as $s) echo "<option value='{$s['status_id']}'>{$s['name']}</option>"; ?>
                </select>
                <button type="submit" name="bulk_action" value="give_status" class="btn-s">감염</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th>이름</th>
                    <th>구분</th>
                    <th>레벨</th>
                    <th>포인트</th>
                    <th width="150">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($members as $m): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?=$m['id']?>"></td>
                    <td style="font-weight: bold;"><?=$m['name']?></td>
                    <td>
                        <span class="badge <?=$m['role']?>"><?=$m['role']=='admin'?'관리자':'학생'?></span>
                    </td>
                    <td>Lv.<?=$m['level']?></td>
                    <td><?=number_format($m['point'])?> P</td>
                    <td>
                        <button type="button" class="btn-edit" onclick="location.href='admin_member_detail.php?id=<?=$m['id']?>'">수정</button>
                        <?php if($m['role'] !== 'admin'): ?>
                        <button type="submit" formaction="admin_member.php" name="action" value="delete" class="btn-del" onclick="document.getElementsByName('target_id')[0].value='<?=$m['id']?>'; return confirm('삭제?');">삭제</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <input type="hidden" name="target_id" value="">
    </form>
</div>

<script>
function toggleAll(source) {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = source.checked);
}
</script>

</body>
</html>