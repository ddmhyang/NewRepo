<?php
// admin_member_detail.php : 캐릭터 상세 관리 (최종본: 이름/레벨/포인트 수정 가능)
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

$id = isset($_GET['id']) ? to_int($_GET['id']) : 0;
$member = sql_fetch("SELECT * FROM School_Members WHERE id = ?", [$id]);

if (!$member) {
    echo "<script>alert('존재하지 않는 회원입니다.'); location.replace('admin_member.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [POST 요청 처리]
// ---------------------------------------------------------

// A. 기본 정보 & 스텟 수정
if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $name = trim($_POST['name']);
    $pw = trim($_POST['pw']);
    $level = to_int($_POST['level']);
    $point = to_int($_POST['point']);
    $hp_current = to_int($_POST['hp_current']);
    
    // 5대 스텟
    $str = to_int($_POST['stat_str']);
    $dex = to_int($_POST['stat_dex']);
    $con = to_int($_POST['stat_con']);
    $int = to_int($_POST['stat_int']);
    $luk = to_int($_POST['stat_luk']);

    // 최대 체력 자동 계산 (공식: HP = CON)
    $hp_max = $con;

    // 비밀번호 변경 로직
    if ($pw) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        sql_exec("UPDATE School_Members SET 
            name=?, pw=?, level=?, point=?, hp_current=?, hp_max=?,
            stat_str=?, stat_dex=?, stat_con=?, stat_int=?, stat_luk=?
            WHERE id=?", 
            [$name, $hash, $level, $point, $hp_current, $hp_max, $str, $dex, $con, $int, $luk, $id]
        );
    } else {
        sql_exec("UPDATE School_Members SET 
            name=?, level=?, point=?, hp_current=?, hp_max=?,
            stat_str=?, stat_dex=?, stat_con=?, stat_int=?, stat_luk=?
            WHERE id=?", 
            [$name, $level, $point, $hp_current, $hp_max, $str, $dex, $con, $int, $luk, $id]
        );
    }

    echo "<script>alert('정보가 수정되었습니다.'); location.replace('admin_member_detail.php?id=$id');</script>";
    exit;
}

// B. 아이템 지급/수정
if (isset($_POST['action']) && $_POST['action'] === 'update_item') {
    if (isset($_POST['add_mode'])) {
        $item_id = to_int($_POST['item_id']);
        $count = to_int($_POST['count']);
        $exist = sql_fetch("SELECT id FROM School_Inventory WHERE owner_id=? AND item_id=?", [$id, $item_id]);
        if ($exist) sql_exec("UPDATE School_Inventory SET count = count + ? WHERE id=?", [$count, $exist['id']]);
        else sql_exec("INSERT INTO School_Inventory (owner_id, item_id, count) VALUES (?, ?, ?)", [$id, $item_id, $count]);
    } else {
        $inv_id = to_int($_POST['inv_id']);
        $count = to_int($_POST['count']);
        if ($count <= 0) sql_exec("DELETE FROM School_Inventory WHERE id=?", [$inv_id]);
        else sql_exec("UPDATE School_Inventory SET count=? WHERE id=?", [$count, $inv_id]);
    }
    echo "<script>location.replace('admin_member_detail.php?id=$id');</script>";
    exit;
}

// C. 상태이상 부여/수정
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (isset($_POST['add_mode'])) {
        $status_id = to_int($_POST['status_id']);
        $exist = sql_fetch("SELECT id FROM School_Status_Active WHERE target_id=? AND status_id=?", [$id, $status_id]);
        if (!$exist) sql_exec("INSERT INTO School_Status_Active (target_id, status_id) VALUES (?, ?)", [$id, $status_id]);
    } else {
        $act_id = to_int($_POST['active_id']);
        $stage = to_int($_POST['stage']);
        if ($stage <= 0) sql_exec("DELETE FROM School_Status_Active WHERE id=?", [$act_id]);
        else sql_exec("UPDATE School_Status_Active SET current_stage=? WHERE id=?", [$stage, $act_id]);
    }
    echo "<script>location.replace('admin_member_detail.php?id=$id');</script>";
    exit;
}

// 데이터 조회
$all_items = sql_fetch_all("SELECT * FROM School_Item_Info ORDER BY name ASC");
$all_status = sql_fetch_all("SELECT * FROM School_Status_Info ORDER BY name ASC");
$my_items = sql_fetch_all("SELECT inv.*, info.name, info.img_icon FROM School_Inventory inv LEFT JOIN School_Item_Info info ON inv.item_id = info.item_id WHERE inv.owner_id = ? ORDER BY inv.id DESC", [$id]);
$my_status = sql_fetch_all("SELECT active.*, info.name, info.max_stage FROM School_Status_Active active LEFT JOIN School_Status_Info info ON active.status_id = info.status_id WHERE active.target_id = ?", [$id]);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?=$member['name']?> 관리</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Pretendard'; background: #F0F2F5; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; padding-bottom: 50px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn { background: #ddd; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }

        .card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: #CE5961; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: bold; color: #666; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        
        .btn-save { width: 100%; background: #CE5961; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-mini { padding: 5px 10px; border-radius: 5px; border: none; cursor: pointer; font-size: 12px; }
        
        .preview-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; font-weight: bold; color: #555; text-align: center; }
        .list-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .list-table td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-user-pen"></i> <?=$member['name']?> 상세관리</h2>
        <button class="back-btn" onclick="location.href='admin_member.php'">목록으로</button>
    </div>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="update_info">
            <div class="card-title">기본 정보 & 스텟 수정</div>
            
            <div class="form-grid">
                <div><label>캐릭터 이름</label><input type="text" name="name" value="<?=$member['name']?>" required></div>
                <div><label>비밀번호 (변경 시 입력)</label><input type="text" name="pw" placeholder="비워두면 유지"></div>
                <div><label>레벨 (Lv)</label><input type="number" name="level" value="<?=$member['level']?>"></div>
                <div><label>포인트 (P)</label><input type="number" name="point" value="<?=$member['point']?>"></div>
                <div><label>현재 체력</label><input type="number" name="hp_current" value="<?=$member['hp_current']?>"></div>
            </div>

            <hr style="border:0; border-top:1px dashed #ddd; margin: 20px 0;">
            
            <div class="preview-box">
                [전투 스텟 자동계산 미리보기]<br>
                <span id="pv-atk">공격력: 0</span> | <span id="pv-def">방어력: 0</span> | 
                <span id="pv-hp">체력: 0</span> | <span id="pv-spd">스피드: 0</span>
            </div>

            <div class="form-grid" style="grid-template-columns: repeat(5, 1fr);">
                <div><label>근력(STR)</label><input type="number" name="stat_str" class="st-in" value="<?=$member['stat_str']?>"></div>
                <div><label>민첩(DEX)</label><input type="number" name="stat_dex" class="st-in" value="<?=$member['stat_dex']?>"></div>
                <div><label>체력(CON)</label><input type="number" name="stat_con" class="st-in" value="<?=$member['stat_con']?>"></div>
                <div><label>지능(INT)</label><input type="number" name="stat_int" class="st-in" value="<?=$member['stat_int']?>"></div>
                <div><label>행운(LUK)</label><input type="number" name="stat_luk" class="st-in" value="<?=$member['stat_luk']?>"></div>
            </div>

            <button type="submit" class="btn-save">정보 및 스텟 업데이트</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">소지품 관리</div>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:15px; background:#f9f9f9; padding:10px; border-radius:8px;">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="add_mode" value="1">
            <select name="item_id" style="flex:2">
                <?php foreach($all_items as $i) echo "<option value='{$i['item_id']}'>{$i['name']}</option>"; ?>
            </select>
            <input type="number" name="count" value="1" placeholder="개수" style="flex:1">
            <button type="submit" class="btn-mini" style="background:#555; color:white;">지급</button>
        </form>

        <table class="list-table">
            <?php foreach($my_items as $inv): ?>
            <tr>
                <td width="40"><?=$inv['img_icon']?></td>
                <td><b><?=$inv['name']?></b></td>
                <td align="right">
                    <form method="POST" style="display:flex; gap:5px; justify-content:flex-end;">
                        <input type="hidden" name="action" value="update_item">
                        <input type="hidden" name="inv_id" value="<?=$inv['id']?>">
                        <input type="number" name="count" value="<?=$inv['count']?>" style="width:60px; padding:5px;">
                        <button type="submit" class="btn-mini" style="background:#AED1D5;">수정</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-title">상태이상 관리</div>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:15px; background:#f9f9f9; padding:10px; border-radius:8px;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="add_mode" value="1">
            <select name="status_id" style="flex:2">
                <?php foreach($all_status as $s) echo "<option value='{$s['status_id']}'>{$s['name']}</option>"; ?>
            </select>
            <button type="submit" class="btn-mini" style="background:#e74c3c; color:white;">감염시키기</button>
        </form>

        <table class="list-table">
            <?php foreach($my_status as $st): ?>
            <tr>
                <td><i class="fa-solid fa-biohazard" style="color:#e74c3c"></i> <b><?=$st['name']?></b></td>
                <td align="right">
                    <form method="POST" style="display:flex; gap:5px; justify-content:flex-end;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="active_id" value="<?=$st['id']?>">
                        <select name="stage" style="width:80px; padding:5px;">
                            <option value="0">치료</option>
                            <?php for($i=1; $i<=$st['max_stage']; $i++) echo "<option value='$i' ".($i==$st['current_stage']?'selected':'').">$i 단계</option>"; ?>
                        </select>
                        <button type="submit" class="btn-mini" style="background:#AED1D5;">변경</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
// 스텟 미리보기 (공식: 반올림 적용)
document.querySelectorAll('.st-in').forEach(el => el.addEventListener('input', updateCalc));

function updateCalc() {
    const getVal = (name) => parseInt(document.querySelector(`[name=${name}]`).value) || 0;
    const str = getVal('stat_str');
    const dex = getVal('stat_dex');
    const con = getVal('stat_con');
    const int = getVal('stat_int');
    const luk = getVal('stat_luk');

    // 공식: (반올림 적용)
    const atk = Math.round((str*0.4) + (dex*0.3) + (con*0.1) + (luk*0.1) + (int*0.1));
    const def = Math.round((con*0.5) + (dex*0.3) + (int*0.1) + (luk*0.1));
    
    document.getElementById('pv-atk').innerText = "ATK: " + atk;
    document.getElementById('pv-def').innerText = "DEF: " + def;
    document.getElementById('pv-hp').innerText = "MAX HP: " + con;
    document.getElementById('pv-spd').innerText = "SPD: " + dex;
}
updateCalc(); // 초기 로드 시 실행
</script>
</body>
</html>