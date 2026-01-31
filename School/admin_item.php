<?php
// admin_item.php : 아이템 & 상점 관리 (장비 부위 세분화 적용)
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [POST] 데이터 처리
// ---------------------------------------------------------

if (isset($_POST['action']) && ($_POST['action'] === 'create' || $_POST['action'] === 'update')) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $price = to_int($_POST['price']);
    $desc = trim($_POST['desc']);
    $max_dur = to_int($_POST['max_dur']);
    $icon = trim($_POST['icon']) ? trim($_POST['icon']) : '<i class="fa-solid fa-box"></i>';
    
    // [핵심] 효과 데이터 배열로 묶기
    $effects = [];
    if (isset($_POST['eff_hp']) && $_POST['eff_hp'] !== '') $effects['hp_heal'] = to_int($_POST['eff_hp']);
    if (isset($_POST['eff_atk']) && $_POST['eff_atk'] !== '') $effects['atk'] = to_int($_POST['eff_atk']);
    if (isset($_POST['eff_def']) && $_POST['eff_def'] !== '') $effects['def'] = to_int($_POST['eff_def']);
    // 스피드, 스텟 등 추가 가능
    if (isset($_POST['eff_speed']) && $_POST['eff_speed'] !== '') $effects['speed'] = to_int($_POST['eff_speed']);
    if (isset($_POST['eff_str']) && $_POST['eff_str'] !== '') $effects['str'] = to_int($_POST['eff_str']);
    if (isset($_POST['eff_luk']) && $_POST['eff_luk'] !== '') $effects['luk'] = to_int($_POST['eff_luk']);
    
    // 상태이상 효과
    if (!empty($_POST['eff_status_id'])) {
        $effects['status_id'] = to_int($_POST['eff_status_id']);
        $effects['status_act'] = $_POST['eff_status_act'];
    }
    
    // JSON으로 변환하여 DB 저장
    $json_eff = json_encode($effects, JSON_UNESCAPED_UNICODE);

    if ($_POST['action'] === 'create') {
        sql_exec("INSERT INTO School_Item_Info (name, type, price, descr, max_dur, img_icon, effect_data) VALUES (?, ?, ?, ?, ?, ?, ?)", 
            [$name, $type, $price, $desc, $max_dur, $icon, $json_eff]);
    } else {
        $id = to_int($_POST['item_id']);
        sql_exec("UPDATE School_Item_Info SET name=?, type=?, price=?, descr=?, max_dur=?, img_icon=?, effect_data=? WHERE item_id=?", 
            [$name, $type, $price, $desc, $max_dur, $icon, $json_eff, $id]);
    }
    echo "<script>location.replace('admin_item.php');</script>"; exit;
}

// 2. 삭제
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = to_int($_POST['item_id']);
    sql_exec("DELETE FROM School_Item_Info WHERE item_id=?", [$id]);
    echo "<script>location.replace('admin_item.php');</script>"; exit;
}

// 3. 상점 진열 토글
if (isset($_POST['action']) && $_POST['action'] === 'toggle_shop') {
    $id = to_int($_POST['item_id']);
    $exists = sql_fetch("SELECT id FROM School_Shop_Config WHERE item_id=?", [$id]);
    
    if ($exists) {
        sql_exec("DELETE FROM School_Shop_Config WHERE item_id=?", [$id]);
    } else {
        sql_exec("INSERT INTO School_Shop_Config (item_id, stock) VALUES (?, -1)", [$id]);
    }
    echo "<script>location.replace('admin_item.php');</script>";
    exit;
}

// 4. 상점 설정 업데이트 (재고/리필)
if (isset($_POST['action']) && $_POST['action'] === 'update_shop_config') {
    $shop_id = to_int($_POST['shop_id']);
    $stock = to_int($_POST['stock']);
    $refill_type = $_POST['refill_type'];
    $refill_value = $_POST['refill_value'];
    $refill_amount = to_int($_POST['refill_amount']);

    sql_exec("UPDATE School_Shop_Config SET stock=?, refill_type=?, refill_value=?, refill_amount=? WHERE id=?", 
        [$stock, $refill_type, $refill_value, $refill_amount, $shop_id]
    );
    echo "<script>alert('상점 설정이 저장되었습니다.'); location.replace('admin_item.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [조회]
// ---------------------------------------------------------
$items = sql_fetch_all("
    SELECT i.*, s.id as shop_id, s.stock, s.refill_type, s.refill_value, s.refill_amount
    FROM School_Item_Info i 
    LEFT JOIN School_Shop_Config s ON i.item_id = s.item_id 
    ORDER BY i.item_id DESC
");

// 아이템 타입 한글명 매핑
$type_map = [
    'CONSUME' => '소모품',
    'WEAPON' => '무기',
    'HAT' => '모자',
    'FACE' => '얼굴장식',
    'TOP' => '상의',
    'BOTTOM' => '하의',
    'GLOVES' => '장갑',
    'SHOES' => '신발',
    'ETC' => '기타/장신구'
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>아이템 관리 - School RPG</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --secondary: #D67F85; --bg: #F0F2F5; --point: #AED1D5; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn { background: #ddd; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }

        /* 폼 */
        .form-box { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: var(--primary); }
        .input-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: 'Pretendard'; }
        textarea { height: 80px; resize: vertical; margin-bottom: 15px; }
        .btn-add { background: var(--primary); color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: bold; font-size: 16px; cursor: pointer; }

        /* 리스트 */
        .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .item-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 10px; }
        .item-header { display: flex; gap: 10px; align-items: center; }
        .item-icon { font-size: 30px; color: var(--secondary); width: 40px; text-align: center; }
        .item-info { flex: 1; }
        .item-info h3 { margin: 0; font-size: 16px; }
        .item-type-badge { font-size: 12px; color: white; background: #555; padding: 2px 6px; border-radius: 4px; font-weight:bold; }
        
        .item-stats { font-size: 13px; background: #f9f9f9; padding: 8px; border-radius: 6px; color: #555; }
        .item-desc { font-size: 12px; color: #666; line-height: 1.4; }
        
        .shop-config { background: #f0f4f8; padding: 10px; border-radius: 8px; font-size: 12px; border: 1px solid #e1e5e9; }
        .shop-row { display: flex; align-items: center; gap: 5px; margin-bottom: 5px; }
        .shop-config input, .shop-config select { padding: 5px; font-size: 12px; margin: 0; }

        .btn-sm { flex: 1; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: bold; }
        .btn-shop-on { background: #2ecc71; color: white; }
        .btn-shop-off { background: #ddd; color: #777; }
        .btn-del { background: #e74c3c; color: white; flex: 0.3 !important; }
        .action-row { display: flex; gap: 5px; margin-top: auto; }
        
        .edit-mode { border: 2px solid var(--primary); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-shirt"></i> 아이템 & 상점 설정</h2>
        <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
    </div>

    <div class="form-box" id="form-box">
        <div class="form-title">
            <span id="form-mode-txt">신규 아이템 등록</span>
            <button type="button" onclick="resetForm()" id="btn-cancel" style="display:none; float:right; background:#999; border:none; color:white; padding:5px 10px; border-radius:5px; font-size:12px; cursor:pointer;">취소</button>
        </div>
        <form method="POST" id="item-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="item_id" id="form-item-id">
            
            <div class="input-group">
                <select name="type" id="inp-type" required>
                    <option value="" disabled selected>-- 종류 선택 --</option>
                    <optgroup label="소모품/기타">
                        <option value="CONSUME">소모품 (체력회복 등)</option>
                        <option value="ETC">기타 (장신구 등)</option>
                    </optgroup>
                    <optgroup label="장비 아이템">
                        <option value="WEAPON">무기</option>
                        <option value="HAT">모자</option>
                        <option value="FACE">얼굴장식</option>
                        <option value="TOP">상의</option>
                        <option value="BOTTOM">하의</option>
                        <option value="GLOVES">장갑</option>
                        <option value="SHOES">신발</option>
                    </optgroup>
                </select>
                <input type="text" name="name" id="inp-name" placeholder="아이템 이름" required>
                <input type="number" name="price" id="inp-price" placeholder="가격 (P)" required>
            </div>
            
            <div class="input-group">
                <input type="text" name="icon" id="inp-icon" placeholder='아이콘 태그 (예: <i class="fa.."></i>)'>
                <input type="number" name="max_dur" id="inp-dur" placeholder="최대 내구도 (0=무제한)">
            </div>

            <div class="input-group">
                <input type="number" name="eff_atk" id="inp-atk" placeholder="공격력 +">
                <input type="number" name="eff_def" id="inp-def" placeholder="방어력 +">
                <input type="number" name="eff_hp" id="inp-hp" placeholder="체력 회복(소모품)">
            </div>
            <div class="input-group">
                <input type="number" name="eff_str" id="inp-str" placeholder="힘(STR) +">
                <input type="number" name="eff_speed" id="inp-speed" placeholder="스피드(민첩) +">
                <input type="number" name="eff_luk" id="inp-luk" placeholder="행운 +">
            </div>

            <div style="margin-top:10px; border-top:1px dashed #ddd; padding-top:10px;">
                <label>상태이상 효과 (소모품용)</label>
                <div style="display:flex; gap:5px;">
                    <select name="eff_status_act" id="inp-status-act" style="width:100px;">
                        <option value="add">부여 (감염)</option>
                        <option value="cure">치료 (제거)</option>
                        <option value="up">악화 (+1단계)</option>
                        <option value="down">완화 (-1단계)</option>
                    </select>
                    <select name="eff_status_id" id="inp-status-id" style="flex:1;">
                        <option value="">-- 효과 없음 --</option>
                        <?php 
                        $st_list = sql_fetch_all("SELECT status_id, name FROM School_Status_Info");
                        foreach($st_list as $s) echo "<option value='{$s['status_id']}'>{$s['name']}</option>";
                        ?>
                    </select>
                </div>
            </div>

            <textarea name="desc" id="inp-desc" placeholder="아이템 설명" style="margin-top:10px;"></textarea>

            <button type="submit" class="btn-add" id="btn-submit">아이템 생성하기</button>
        </form>
    </div>

    <div class="form-title" style="margin-left: 10px;">등록된 아이템 목록</div>
    <div class="item-grid">
        <?php foreach($items as $item): ?>
            <?php 
                $eff = json_decode($item['effect_data'], true); 
                $type_name = isset($type_map[$item['type']]) ? $type_map[$item['type']] : $item['type'];
            ?>
            <div class="item-card">
                <div class="item-header">
                    <div class="item-icon"><?=$item['img_icon']?></div>
                    <div class="item-info">
                        <h3><?=$item['name']?></h3>
                        <span class="item-type-badge"><?=$type_name?></span> 
                        <span style="color:var(--primary); font-weight:bold; margin-left:5px;"><?=number_format($item['price'])?> P</span>
                    </div>
                </div>

                <div class="item-stats">
                    내구도: <?=$item['max_dur'] > 0 ? $item['max_dur'] : '∞'?><br>
                    <?php if(!empty($eff['atk'])): ?>⚔️ 공격 +<?=$eff['atk']?> <?php endif; ?>
                    <?php if(!empty($eff['def'])): ?>🛡️ 방어 +<?=$eff['def']?> <?php endif; ?>
                    <?php if(!empty($eff['hp_heal'])): ?>❤️ 회복 +<?=$eff['hp_heal']?> <?php endif; ?>
                    <?php if(!empty($eff['str'])): ?>💪 힘 +<?=$eff['str']?> <?php endif; ?>
                    <?php if(!empty($eff['speed'])): ?>💨 속도 +<?=$eff['speed']?> <?php endif; ?>
                    <?php if(!empty($eff['luk'])): ?>🍀 행운 +<?=$eff['luk']?> <?php endif; ?>
                </div>

                <div class="item-desc"><?=htmlspecialchars($item['descr'])?></div>

                <?php if($item['shop_id']): ?>
                <div class="shop-config">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_shop_config">
                        <input type="hidden" name="shop_id" value="<?=$item['shop_id']?>">
                        
                        <div class="shop-row">
                            <span>재고:</span>
                            <input type="number" name="stock" value="<?=$item['stock']?>" style="width:60px;">
                            <span style="color:#999;">(-1:무한)</span>
                        </div>
                        <div class="shop-row" style="border-top:1px dashed #ccc; padding-top:5px; margin-top:5px;">
                            <span>리필:</span>
                            <select name="refill_type" style="width:80px;">
                                <option value="NONE" <?=$item['refill_type']=='NONE'?'selected':''?>>X</option>
                                <option value="DAILY" <?=$item['refill_type']=='DAILY'?'selected':''?>>매일</option>
                                <option value="TIME" <?=$item['refill_type']=='TIME'?'selected':''?>>시간</option>
                            </select>
                        </div>
                        <div class="shop-row">
                            <input type="text" name="refill_value" value="<?=$item['refill_value']?>" placeholder="HH:mm / 분">
                            <input type="number" name="refill_amount" value="<?=$item['refill_amount']?>" placeholder="양" style="width:50px;">
                        </div>
                        <button type="submit" style="width:100%; background:#7f8c8d; color:white; border:none; border-radius:4px; padding:5px; margin-top:5px; cursor:pointer;">설정 저장</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="action-row">
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="action" value="toggle_shop">
                        <input type="hidden" name="item_id" value="<?=$item['item_id']?>">
                        <button type="submit" class="btn-sm <?=$item['shop_id']?'btn-shop-on':'btn-shop-off'?>">
                            <?=$item['shop_id']?'판매중':'미판매'?>
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('삭제하시겠습니까?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="item_id" value="<?=$item['item_id']?>">
                        <button type="button" class="btn-sm" style="background:#AED1D5; color:#333;" 
                        onclick='editItem(<?=json_encode(array_merge($item, $eff?:[]))?>)'>
                        수정
                    </button>
                        <button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function editItem(data) {
    // 모드 전환
    document.getElementById('form-box').classList.add('edit-mode');
    document.getElementById('form-mode-txt').textContent = '아이템 수정: ' + data.name;
    document.getElementById('btn-submit').textContent = '수정내용 저장';
    document.getElementById('btn-cancel').style.display = 'block';

    // 기본 데이터 채우기
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-item-id').value = data.item_id;
    document.getElementById('inp-name').value = data.name;
    document.getElementById('inp-type').value = data.type;
    document.getElementById('inp-price').value = data.price;
    document.getElementById('inp-dur').value = data.max_dur;
    document.getElementById('inp-icon').value = data.img_icon;
    document.getElementById('inp-desc').value = data.descr;

    // JSON 데이터 파싱
    let eff = {};
    try {
        if (typeof data.effect_data === 'string') eff = JSON.parse(data.effect_data);
        else if (data.effect_data) eff = data.effect_data;
    } catch(e) { console.error(e); }

    // 효과 데이터 채우기
    document.getElementById('inp-hp').value = eff.hp_heal || '';
    document.getElementById('inp-atk').value = eff.atk || '';
    document.getElementById('inp-def').value = eff.def || '';
    document.getElementById('inp-str').value = eff.str || '';
    document.getElementById('inp-speed').value = eff.speed || '';
    document.getElementById('inp-luk').value = eff.luk || '';
    
    document.getElementById('inp-status-id').value = eff.status_id || '';
    document.getElementById('inp-status-act').value = eff.status_act || 'add';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-box').classList.remove('edit-mode');
    document.getElementById('form-mode-txt').textContent = '신규 아이템 등록';
    document.getElementById('btn-cancel').style.display = 'none';
    document.getElementById('btn-submit').textContent = '아이템 생성하기';
    
    document.getElementById('item-form').reset();
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-item-id').value = '';
}
</script>
</body>
</html>