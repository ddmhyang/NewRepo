<?php
// admin_inventory_all.php : 전체 유저 소지품 통합 관리
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// 수정 로직 (수량 변경/삭제)
if (isset($_POST['action']) && $_POST['action'] === 'update_count') {
    $inv_id = to_int($_POST['inv_id']);
    $count = to_int($_POST['count']);
    
    if ($count <= 0) {
        // 수량이 0 이하면 삭제
        sql_exec("DELETE FROM School_Inventory WHERE id=?", [$inv_id]);
    } else {
        // 수량 업데이트
        sql_exec("UPDATE School_Inventory SET count=? WHERE id=?", [$count, $inv_id]);
    }
    // 페이지 새로고침 (변경사항 반영)
    echo "<script>location.replace('admin_inventory_all.php');</script>";
    exit;
}

// 전체 소지품 조회 (유저 이름순 정렬)
$list = sql_fetch_all("
    SELECT inv.*, m.name as user_name, i.name as item_name, i.type 
    FROM School_Inventory inv
    JOIN School_Members m ON inv.owner_id = m.id
    JOIN School_Item_Info i ON inv.item_id = i.item_id
    ORDER BY m.name ASC, i.type ASC, i.name ASC
");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>소지품 통합 관리</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --bg: #F0F2F5; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { margin: 0; display: flex; align-items: center; gap: 10px; color: var(--primary); }
        .back-btn { background: #eee; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; color: #333; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; background: #f8f8f8; color: #555; border-bottom: 2px solid #eee; font-size: 14px; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 15px; vertical-align: middle; }
        tr:hover { background: #fdfdfd; }

        .count-input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn-update { background: #333; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 13px; }
        .btn-update:hover { background: #555; }
        
        .badge { font-size: 11px; padding: 3px 6px; border-radius: 4px; background: #eee; color: #777; margin-right: 5px; }
        .equipped { color: var(--primary); font-weight: bold; font-size: 12px; margin-left: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-boxes-stacked"></i> 전체 소지품 현황</h2>
        <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>소유자</th>
                <th>아이템 정보</th>
                <th>수량 관리</th>
                <th>작업</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($list as $row): ?>
            <tr>
                <td style="font-weight:bold;"><?=$row['user_name']?></td>
                <td>
                    <span class="badge"><?=$row['type']?></span>
                    <?=$row['item_name']?>
                    <?php if($row['is_equipped']): ?>
                        <span class="equipped"><i class="fa-solid fa-e"></i> 장착중</span>
                    <?php endif; ?>
                </td>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_count">
                    <input type="hidden" name="inv_id" value="<?=$row['id']?>">
                    
                    <td>
                        <input type="number" name="count" value="<?=$row['count']?>" class="count-input">
                    </td>
                    <td>
                        <button type="submit" class="btn-update">수정</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
            
            <?php if(!$list): ?>
                <tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">소지품 데이터가 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>