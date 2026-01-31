<?php
// admin_monster.php : 몬스터 관리 (스텟 자동계산 + 보상 + 페널티 포함 완전판)
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [POST] 데이터 처리
// ---------------------------------------------------------

// 1. 몬스터 생성 및 수정
if (isset($_POST['action']) && ($_POST['action'] === 'create' || $_POST['action'] === 'update')) {
    $name = trim($_POST['name']);
    
    // 5대 스텟 받기
    $str = to_int($_POST['stat_str']);
    $dex = to_int($_POST['stat_dex']);
    $con = to_int($_POST['stat_con']);
    $int = to_int($_POST['stat_int']);
    $luk = to_int($_POST['stat_luk']);

    // 서버 측 자동 계산 (공식 적용)
    $atk = round(($str*0.4) + ($dex*0.3) + ($con*0.1) + ($luk*0.1) + ($int*0.1));
    $def = round(($con*0.5) + ($dex*0.3) + ($int*0.1) + ($luk*0.1));
    $hp = $con;   // HP는 체력 스텟 그대로
    $speed = $dex; // 스피드는 민첩 스텟 그대로

    // DB에 저장할 스텟 JSON 생성
    $stats = [
        'hp' => $hp, 'atk' => $atk, 'def' => $def, 'speed' => $speed,
        'str' => $str, 'dex' => $dex, 'con' => $con, 'int' => $int, 'luk' => $luk
    ];
    $json_stats = json_encode($stats);
    
    // 드랍 아이템 설정
    $drop_items = [];
    if (!empty($_POST['drop_id']) && !empty($_POST['drop_rate'])) {
        $drop_items[$_POST['drop_id']] = to_int($_POST['drop_rate']);
    }
    $json_drop = json_encode($drop_items);

    // [보상] 경험치 & 포인트
    $give_exp = to_int($_POST['give_exp']);
    $give_point = to_int($_POST['give_point']);

    // [페널티] 패배 시 설정
    $penalty = [
        'point' => to_int($_POST['pen_point']),   // 포인트 증감 (음수 가능)
        'status' => to_int($_POST['pen_status'])  // 상태이상 ID
    ];
    $json_penalty = json_encode($penalty);

    // DB 입력
    if ($_POST['action'] === 'create') {
        sql_exec("INSERT INTO School_Monsters (name, stats, skills, drop_items, give_exp, give_point, defeat_penalty) VALUES (?, ?, '[]', ?, ?, ?, ?)", 
            [$name, $json_stats, $json_drop, $give_exp, $give_point, $json_penalty]
        );
    } else {
        $mob_id = to_int($_POST['mob_id']);
        sql_exec("UPDATE School_Monsters SET name=?, stats=?, drop_items=?, give_exp=?, give_point=?, defeat_penalty=? WHERE mob_id=?", 
            [$name, $json_stats, $json_drop, $give_exp, $give_point, $json_penalty, $mob_id]
        );
    }
    echo "<script>location.replace('admin_monster.php');</script>";
    exit;
}

// 2. 삭제
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = to_int($_POST['mob_id']);
    sql_exec("DELETE FROM School_Monsters WHERE mob_id=?", [$id]);
    echo "<script>alert('삭제되었습니다.'); location.replace('admin_monster.php');</script>";
    exit;
}

// ---------------------------------------------------------
// [조회]
// ---------------------------------------------------------
$monsters = sql_fetch_all("SELECT * FROM School_Monsters ORDER BY mob_id DESC");
$items = sql_fetch_all("SELECT item_id, name FROM School_Item_Info ORDER BY name ASC");
$status_list = sql_fetch_all("SELECT status_id, name FROM School_Status_Info ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>몬스터 관리 - School RPG</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Pretendard', sans-serif; background: #F0F2F5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn { background: #ddd; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }

        /* 입력 폼 */
        .form-box { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: #CE5961; display: flex; justify-content: space-between; align-items: center; }
        
        .input-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-bottom: 15px; }
        .input-label { font-size: 12px; font-weight: bold; color: #666; margin-bottom: 5px; display: block; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: 'Pretendard'; }
        
        .readonly-stat { background: #eee; color: #555; pointer-events: none; }
        
        .btn-submit { background: #333; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: bold; font-size: 16px; cursor: pointer; }
        .btn-submit.update { background: #CE5961; }
        .btn-cancel { background: #999; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; display: none; }

        /* 리스트 */
        .mob-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .mob-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative; transition: 0.2s; }
        .mob-card:hover { transform: translateY(-5px); }
        
        .mob-name { font-size: 18px; font-weight: 800; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .mob-stats { font-size: 13px; color: #555; line-height: 1.6; background: #f9f9f9; padding: 10px; border-radius: 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .mob-drop { margin-top: 10px; font-size: 13px; color: #CE5961; font-weight: bold; }
        .mob-penalty { margin-top: 5px; font-size: 12px; color: #e74c3c; background: #fff0f0; padding: 5px; border-radius: 5px; }
        
        .card-actions { position: absolute; top: 15px; right: 15px; display: flex; gap: 5px; }
        .btn-sm { border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-edit { background: #AED1D5; color: #333; }
        .btn-del { background: #eee; color: #e74c3c; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-skull"></i> 몬스터 & 모험 설정</h2>
        <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
    </div>

    <div class="form-box" id="form-box">
        <div class="form-title">
            <span id="form-mode-txt">새 몬스터 출현시키기</span>
            <button type="button" class="btn-cancel" id="btn-cancel" onclick="resetForm()">취소</button>
        </div>
        <form method="POST" id="mob-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="mob_id" id="form-mob-id">
            
            <input type="text" name="name" id="inp-name" placeholder="몬스터 이름 (예: 복도 당직선생님 좀비)" required style="margin-bottom: 15px;">
            
            <p style="font-weight:bold; margin-bottom:5px; font-size:14px; color:#555;">▼ 5대 스텟 입력 (자동 계산됨)</p>
            <div class="input-group" style="grid-template-columns: repeat(5, 1fr);">
                <div><span class="input-label">근력</span><input type="number" name="stat_str" class="calc-trigger" value="10"></div>
                <div><span class="input-label">민첩</span><input type="number" name="stat_dex" class="calc-trigger" value="10"></div>
                <div><span class="input-label">체력</span><input type="number" name="stat_con" class="calc-trigger" value="10"></div>
                <div><span class="input-label">지능</span><input type="number" name="stat_int" class="calc-trigger" value="10"></div>
                <div><span class="input-label">행운</span><input type="number" name="stat_luk" class="calc-trigger" value="10"></div>
            </div>

            <p style="font-weight:bold; margin-bottom:5px; font-size:14px; color:#CE5961;">▼ 전투 스텟 (수정 불가)</p>
            <div class="input-group" style="grid-template-columns: repeat(4, 1fr);">
                <div><span class="input-label">HP</span><input type="number" name="hp" class="readonly-stat" readonly></div>
                <div><span class="input-label">ATK</span><input type="number" name="atk" class="readonly-stat" readonly></div>
                <div><span class="input-label">DEF</span><input type="number" name="def" class="readonly-stat" readonly></div>
                <div><span class="input-label">SPD</span><input type="number" name="speed" class="readonly-stat" readonly></div>
            </div>

            <div class="input-group" style="background:#e8f8f5; padding:10px; border-radius:8px; display:flex; gap:15px;">
                <div style="flex:1">
                    <span class="input-label" style="color:green;">획득 경험치(Exp)</span>
                    <input type="number" name="give_exp" id="inp-exp" value="10">
                </div>
                <div style="flex:1">
                    <span class="input-label" style="color:green;">획득 포인트(P)</span>
                    <input type="number" name="give_point" id="inp-point" value="50">
                </div>
            </div>

            <div class="input-group" style="background:#f4f4f4; padding:10px; border-radius:8px; display:block;">
                <span class="input-label" style="margin-bottom:8px;">드랍 아이템 설정</span>
                <div style="display:flex; gap:10px;">
                    <select name="drop_id" style="flex:2;">
                        <option value="">-- 드랍 없음 --</option>
                        <?php foreach($items as $itm): ?>
                            <option value="<?=$itm['item_id']?>"><?=$itm['name']?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="drop_rate" placeholder="확률 (%)" style="flex:1;">
                </div>
            </div>

            <div class="input-group" style="background:#fff0f0; padding:10px; border-radius:8px; display:block;">
                <span class="input-label" style="margin-bottom:8px; color:#c0392b;">패배 페널티 (유저 패배 시)</span>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="pen_point" id="inp-pen-pt" placeholder="포인트 증감 (예: -100)" style="flex:1;">
                    <select name="pen_status" id="inp-pen-st" style="flex:1;">
                        <option value="">상태이상 없음</option>
                        <?php foreach($status_list as $s) echo "<option value='{$s['status_id']}'>{$s['name']}</option>"; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btn-submit"><i class="fa-solid fa-plus"></i> 몬스터 도감 등록</button>
        </form>
    </div>

    <div class="mob-list">
        <?php foreach($monsters as $mob): ?>
            <?php 
                $st = json_decode($mob['stats'], true); 
                $dr = json_decode($mob['drop_items'], true);
                $pn = json_decode($mob['defeat_penalty'], true);
                
                // 드랍 텍스트
                $d_id = ''; $d_rate = ''; $d_text = "드랍 없음";
                if ($dr) {
                    foreach($dr as $did => $rate) {
                        $d_id = $did; $d_rate = $rate;
                        foreach($items as $i) { if($i['item_id'] == $did) { $d_text = $i['name']." (".$rate."%)"; break; } }
                    }
                }

                // 페널티 텍스트
                $pen_text = [];
                if (!empty($pn['point'])) $pen_text[] = $pn['point']." P";
                if (!empty($pn['status'])) {
                    foreach($status_list as $sl) if($sl['status_id'] == $pn['status']) { $pen_text[] = $sl['name']; break; }
                }
                $pen_str = empty($pen_text) ? "없음" : implode(", ", $pen_text);

                // JS 데이터 준비
                $mobData = [
                    'id' => $mob['mob_id'], 'name' => $mob['name'],
                    'str' => $st['str'] ?? 10, 'dex' => $st['dex'] ?? 10,
                    'con' => $st['con'] ?? 10, 'int' => $st['int'] ?? 10, 'luk' => $st['luk'] ?? 10,
                    'drop_id' => $d_id, 'drop_rate' => $d_rate,
                    'give_exp' => $mob['give_exp'], 'give_point' => $mob['give_point'],
                    'pen_point' => $pn['point'] ?? '', 'pen_status' => $pn['status'] ?? ''
                ];
            ?>
            <div class="mob-card">
                <div class="mob-name"><i class="fa-solid fa-ghost"></i> <?=$mob['name']?></div>
                
                <div class="mob-stats">
                    <div>❤️ HP: <b><?=$st['hp']?></b></div>
                    <div>⚔️ ATK: <b><?=$st['atk']?></b></div>
                    <div>🛡️ DEF: <b><?=$st['def']?></b></div>
                    <div>⚡ SPD: <b><?=$st['speed']?></b></div>
                </div>
                
                <div style="margin-top:10px; font-size:12px; background:#e8f8f5; padding:5px; border-radius:5px;">
                    보상: Exp +<?=$mob['give_exp']?>, Point +<?=$mob['give_point']?>
                </div>

                <div class="mob-drop">🎁 <?=$d_text?></div>
                <div class="mob-penalty">💀 패배시: <?=$pen_str?></div>

                <div class="card-actions">
                    <button class="btn-sm btn-edit" onclick='editMob(<?=json_encode($mobData)?>)'><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" onsubmit="return confirm('삭제하시겠습니까?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="mob_id" value="<?=$mob['mob_id']?>">
                        <button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// 1. 자동 계산
document.querySelectorAll('.calc-trigger').forEach(input => {
    input.addEventListener('input', calculateStats);
});

function calculateStats() {
    const form = document.getElementById('mob-form');
    const val = (name) => parseInt(form.querySelector(`[name=${name}]`).value) || 0;
    
    const str = val('stat_str');
    const dex = val('stat_dex');
    const con = val('stat_con');
    const int = val('stat_int');
    const luk = val('stat_luk');

    const atk = Math.round((str*0.4) + (dex*0.3) + (con*0.1) + (luk*0.1) + (int*0.1));
    const def = Math.round((con*0.5) + (dex*0.3) + (int*0.1) + (luk*0.1));
    
    form.querySelector('[name=hp]').value = con;
    form.querySelector('[name=atk]').value = atk;
    form.querySelector('[name=def]').value = def;
    form.querySelector('[name=speed]').value = dex;
}

// 2. 수정 모드
function editMob(data) {
    const form = document.getElementById('mob-form');
    document.getElementById('form-box').classList.add('edit-mode');
    document.getElementById('form-mode-txt').textContent = "몬스터 정보 수정";
    document.getElementById('btn-cancel').style.display = 'inline-block';
    
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-mob-id').value = data.id;
    
    const btn = document.getElementById('btn-submit');
    btn.innerHTML = '<i class="fa-solid fa-check"></i> 수정사항 저장';
    btn.classList.add('update');

    // 값 채우기
    form.querySelector('[name=name]').value = data.name;
    form.querySelector('[name=stat_str]').value = data.str;
    form.querySelector('[name=stat_dex]').value = data.dex;
    form.querySelector('[name=stat_con]').value = data.con;
    form.querySelector('[name=stat_int]').value = data.int;
    form.querySelector('[name=stat_luk]').value = data.luk;
    
    document.getElementById('inp-exp').value = data.give_exp;
    document.getElementById('inp-point').value = data.give_point;

    form.querySelector('[name=drop_id]').value = data.drop_id;
    form.querySelector('[name=drop_rate]').value = data.drop_rate;
    
    document.getElementById('inp-pen-pt').value = data.pen_point;
    document.getElementById('inp-pen-st').value = data.pen_status;

    calculateStats();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// 3. 초기화
function resetForm() {
    const form = document.getElementById('mob-form');
    document.getElementById('form-box').classList.remove('edit-mode');
    document.getElementById('form-mode-txt').textContent = "새 몬스터 출현시키기";
    document.getElementById('btn-cancel').style.display = 'none';
    
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-mob-id').value = '';
    
    const btn = document.getElementById('btn-submit');
    btn.innerHTML = '<i class="fa-solid fa-plus"></i> 몬스터 도감 등록';
    btn.classList.remove('update');

    form.reset();
    
    // 기본값 복구
    ['stat_str','stat_dex','stat_con','stat_int','stat_luk'].forEach(n => {
        form.querySelector(`[name=${n}]`).value = 10;
    });
    document.getElementById('inp-exp').value = 10;
    document.getElementById('inp-point').value = 50;

    calculateStats();
}

calculateStats(); // 초기 실행
</script>
</body>
</html>