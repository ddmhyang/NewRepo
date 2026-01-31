<?php
// inventory.php : 내 가방 (장착/해제/사용 기능 완벽 구현, 파일 업로드 포함)
require_once 'common.php';

if (!isset($_SESSION['uid'])) { header("Location: index.php"); exit; }
$my_id = $_SESSION['uid'];

// 내 정보
$me = sql_fetch("SELECT * FROM School_Members WHERE id=?", [$my_id]);

// 아이템 목록
$items = sql_fetch_all("
    SELECT inv.*, info.name, info.type, info.img_icon, info.effect_data, info.max_dur, info.descr
    FROM School_Inventory inv
    JOIN School_Item_Info info ON inv.item_id = info.item_id
    WHERE inv.owner_id = ?
    ORDER BY inv.is_equipped DESC, info.type ASC
", [$my_id]);

// [스텟 계산]
$str=$me['stat_str']; $dex=$me['stat_dex']; $con=$me['stat_con']; $int=$me['stat_int']; $luk=$me['stat_luk'];

// 1. 기본 전투력
$base_atk = round(($str*0.4)+($dex*0.3)+($con*0.1)+($luk*0.1)+($int*0.1));
$base_def = round(($con*0.5)+($dex*0.3)+($int*0.1)+($luk*0.1));

// 2. 장비 보정 합산
$add_atk = 0; 
$add_def = 0;
foreach($items as $it) {
    if($it['is_equipped']) {
        $eff = json_decode($it['effect_data'], true);
        if(isset($eff['atk'])) $add_atk += $eff['atk'];
        if(isset($eff['def'])) $add_def += $eff['def'];
    }
}

// 3. 상태이상 보정 합산
$st_list = sql_fetch_all("
    SELECT act.current_stage, info.stage_config 
    FROM School_Status_Active act
    JOIN School_Status_Info info ON act.status_id = info.status_id
    WHERE act.target_id = ?
", [$my_id]);

$st_atk = 0; 
$st_def = 0;

foreach($st_list as $st) {
    $cfg = json_decode($st['stage_config'], true);
    $stage = $st['current_stage'];
    if(isset($cfg[$stage])) {
        $st_atk += ($cfg[$stage]['atk'] ?? 0);
        $st_def += ($cfg[$stage]['def'] ?? 0);
    }
}

// [최종 스텟]
$final_atk = $base_atk + $add_atk + $st_atk;
$final_def = $base_def + $add_def + $st_def;
$final_spd = $dex; // 스피드는 민첩

if($final_atk < 1) $final_atk = 1;
if($final_def < 0) $final_def = 0;

// 상태이상 목록
$status_list = sql_fetch_all("
    SELECT act.*, info.name 
    FROM School_Status_Active act
    JOIN School_Status_Info info ON act.status_id = info.status_id
    WHERE act.target_id = ?
", [$my_id]);

// 전체 유저 목록 (양도용)
$users = sql_fetch_all("SELECT id, name FROM School_Members WHERE id != ? ORDER BY name ASC", [$my_id]);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 가방</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Pretendard'; background: #F0F2F5; margin: 0; padding-bottom: 50px; }
        .header { background: #CE5961; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .back-btn { color: white; font-size: 20px; cursor: pointer; }
        
        .profile-sec { background: white; padding: 20px; text-align: center; margin-bottom: 10px; }
        .pf-img { width: 80px; height: 80px; border-radius: 50%; background: #eee; margin: 0 auto 10px; overflow: hidden; position: relative; cursor: pointer; border: 3px solid #CE5961; }
        .pf-img img { width: 100%; height: 100%; object-fit: cover; }
        .pf-img i { line-height: 80px; font-size: 30px; color: #ccc; }
        .pf-info h2 { margin: 0; font-size: 20px; }
        .pf-stat { display: flex; justify-content: center; gap: 15px; margin-top: 10px; font-size: 14px; color: #555; }
        .pf-detail { 
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; 
            margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 10px; 
            font-size: 12px; color: #333; font-weight: bold;
        }
        .pf-detail div { display: flex; flex-direction: column; align-items: center; gap: 3px; }
        
        .tabs { display: flex; background: white; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 10; }
        .tab { flex: 1; padding: 15px; text-align: center; cursor: pointer; font-weight: bold; color: #999; border-bottom: 3px solid transparent; }
        .tab.active { color: #CE5961; border-color: #CE5961; }
        
        .content-area { display: none; padding: 15px; }
        .content-area.active { display: block; }
        
        .inv-item { background: white; padding: 15px; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .item-head { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .item-icon { font-size: 24px; color: #CE5961; width: 40px; text-align: center; }
        .item-main { flex: 1; }
        .item-detail { display: none; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px; font-size: 13px; color: #666; }
        .item-detail.show { display: block; }
        
        .dur-bar { width: 100%; height: 4px; background: #eee; border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .dur-fill { height: 100%; background: #27ae60; }
        
        .transfer-box { background: white; padding: 20px; border-radius: 10px; }
        select, input { width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-send { width: 100%; background: #3498db; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="header">
    <i class="fa-solid fa-arrow-left back-btn" onclick="location.href='index.php'"></i>
    <span style="font-weight:bold;">내 정보 & 가방</span>
    <div style="width:20px;"></div>
</div>

<div class="profile-sec">
    <div class="pf-img" onclick="editProfileImage()">
        <?php if(isset($me['img_profile']) && $me['img_profile']): ?>
            <img src="<?=$me['img_profile']?>">
        <?php else: ?>
            <i class="fa-solid fa-user"></i>
        <?php endif; ?>
    </div>
    <div class="pf-info">
        <h2><?=$me['name']?> <span style="font-size:14px; color:#777;">Lv.<?=$me['level']?></span></h2>
        <div class="pf-stat">
            <span>체력 <?=$me['hp_current']?>/<?=$me['hp_max']?></span>
            <span>포인트 <?=number_format($me['point'])?> P</span>
        </div>
        <div class="pf-detail">
            <div title="공격력">공격력 <?=$final_atk?></div>
            <div title="방어력">방어력 <?=$final_def?></div>
            <div title="스피드">스피드 <?=$final_spd?></div>
            <div title="지능">지능 <?=$int?></div>
            <div title="행운">행운 <?=$luk?></div>
        </div>
    </div>
</div>

<div class="tabs">
    <div class="tab active" onclick="setTab('inv')">소지품</div>
    <div class="tab" onclick="setTab('status')">상태이상</div>
    <div class="tab" onclick="setTab('transfer')">양도</div>
</div>

<div id="tab-inv" class="content-area active">
    <?php foreach($items as $item): ?>
        <div class="inv-item">
            <div class="item-head" onclick="toggleDetail(this)">
                <div class="item-icon"><?=$item['img_icon']?></div>
                <div class="item-main">
                    <div style="font-weight:bold;">
                        <?=$item['name']?> 
                        <?php if($item['is_equipped']): ?>
                            <span style="color:#CE5961; font-size:12px;">(E)</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:12px; color:#888;">x<?=$item['count']?></div>
                    
                    <?php 
                    // [수정] 내구도 표시 로직 (current_dur -> cur_dur)
                    if($item['max_dur'] > 0): 
                        // DB에 cur_dur가 없다면 0으로 처리하여 오류 방지
                        $cur_dur = isset($item['cur_dur']) ? $item['cur_dur'] : (isset($item['current_dur']) ? $item['current_dur'] : $item['max_dur']);
                        $per = ($cur_dur / $item['max_dur']) * 100; 
                    ?>
                        <div class="dur-bar"><div class="dur-fill" style="width:<?=$per?>%"></div></div>
                    <?php endif; ?>
                </div>
                <i class="fa-solid fa-chevron-down" style="color:#ccc;"></i>
            </div>
            
            <div class="item-detail">
                <p><?=$item['descr']?></p>
                <?php if($item['max_dur'] > 0): ?>
                    <p>내구도: <?=$cur_dur?> / <?=$item['max_dur']?></p>
                <?php endif; ?>
                
                <div style="display:flex; gap:5px; margin-top:10px;">
                    <?php if($item['type'] === 'consumable' || $item['type'] === 'CONSUME'): ?>
                        <button onclick="itemAction(<?=$item['id']?>, 'use')" style="flex:1; padding:8px; background:#CE5961; color:white; border:none; border-radius:5px;">사용</button>
                    <?php else: ?>
                        <?php if($item['is_equipped']): ?>
                            <button onclick="itemAction(<?=$item['id']?>, 'unequip')" style="flex:1; padding:8px; background:#999; color:white; border:none; border-radius:5px;">해제</button>
                        <?php else: ?>
                            <button onclick="itemAction(<?=$item['id']?>, 'equip')" style="flex:1; padding:8px; background:#333; color:white; border:none; border-radius:5px;">장착</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if(!$items) echo "<div style='text-align:center; padding:30px; color:#999;'>비어있음</div>"; ?>
</div>

<div id="tab-status" class="content-area">
    <?php foreach($status_list as $st): ?>
        <?php 
            $cfg = json_decode($st['stage_config'], true);
            $cur = $st['current_stage'];
            $eff_txt = [];
            if(isset($cfg[$cur])) {
                if(!empty($cfg[$cur]['atk'])) $eff_txt[] = "공격 " . ($cfg[$cur]['atk'] > 0 ? "+" : "") . $cfg[$cur]['atk'];
                if(!empty($cfg[$cur]['def'])) $eff_txt[] = "방어 " . ($cfg[$cur]['def'] > 0 ? "+" : "") . $cfg[$cur]['def'];
            }
            $eff_str = empty($eff_txt) ? "" : " (" . implode(", ", $eff_txt) . ")";
        ?>
        <div class="inv-item">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-biohazard" style="color:#e74c3c; font-size:24px;"></i>
                <div>
                    <b><?=$st['name']?></b> <span style="color:#e74c3c; font-size:12px; font-weight:bold;"><?=$eff_str?></span><br>
                    <span style="font-size:12px; color:#666;">현재 <?=$st['current_stage']?> 단계</span>
                    <div style="font-size:12px; color:#888; margin-top:2px;">
                        <?= isset($cfg[$cur]['desc']) ? $cfg[$cur]['desc'] : '' ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if(!$status_list) echo "<div style='text-align:center; padding:30px; color:#999;'>건강합니다.</div>"; ?>
</div>

<div id="tab-transfer" class="content-area">
    <div class="transfer-box">
        <h3><i class="fa-solid fa-paper-plane"></i> 선물하기</h3>
        
        <label>받는 사람</label>
        <select id="tf-target">
            <option value="">선택하세요</option>
            <?php foreach($users as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?>
        </select>

        <label>보낼 것</label>
        <select id="tf-type" onchange="toggleTfType()">
            <option value="point">포인트 (Point)</option>
            <option value="item">아이템 (Item)</option>
        </select>

        <div id="tf-point-area">
            <input type="number" id="tf-amount" placeholder="보낼 금액">
        </div>

        <div id="tf-item-area" style="display:none;">
            <select id="tf-item-id">
                <option value="">아이템 선택</option>
                <?php foreach($items as $i) if(!$i['is_equipped']) echo "<option value='{$i['id']}'>{$i['name']} (x{$i['count']})</option>"; ?>
            </select>
            <input type="number" id="tf-item-count" value="1" placeholder="수량">
        </div>

        <button class="btn-send" onclick="sendTransfer()">보내기</button>
    </div>
</div>

<input type="file" id="profile-upload" style="display:none;" accept="image/*" onchange="uploadProfileImage(this)">

<script>
// 탭 전환
function setTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
    
    if(name=='inv') { document.querySelectorAll('.tab')[0].classList.add('active'); document.getElementById('tab-inv').classList.add('active'); }
    if(name=='status') { document.querySelectorAll('.tab')[1].classList.add('active'); document.getElementById('tab-status').classList.add('active'); }
    if(name=='transfer') { document.querySelectorAll('.tab')[2].classList.add('active'); document.getElementById('tab-transfer').classList.add('active'); }
}

// 아이템 상세 토글
function toggleDetail(head) {
    const detail = head.nextElementSibling;
    detail.classList.toggle('show');
}

// 아이템 사용/장착/해제 로직
async function itemAction(id, type) {
    if(type=='use' && !confirm("사용하시겠습니까?")) return;
    
    let cmd = 'use_item';
    if (type === 'equip' || type === 'unequip') cmd = 'inventory_action'; // [중요] action 통합 cmd 사용
    
    try {
        const res = await fetch('api.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({cmd: 'inventory_action', inv_id:id, action: type}) // cmd 통일
        }).then(r=>r.json());
        
        if(res.status=='success') { alert(res.msg || '완료되었습니다.'); location.reload(); }
        else alert(res.message);
    } catch(e){ alert('오류'); }
}

// 양도 타입 토글
function toggleTfType() {
    const type = document.getElementById('tf-type').value;
    document.getElementById('tf-point-area').style.display = (type=='point') ? 'block' : 'none';
    document.getElementById('tf-item-area').style.display = (type=='item') ? 'block' : 'none';
}

// 양도 전송
async function sendTransfer() {
    const target = document.getElementById('tf-target').value;
    const type = document.getElementById('tf-type').value;
    if(!target) return alert("받는 사람을 선택하세요.");

    let data = { cmd:'transfer', target_id:target, type:type };
    
    if(type === 'point') {
        data.amount = document.getElementById('tf-amount').value;
    } else {
        data.inv_id = document.getElementById('tf-item-id').value;
        data.count = document.getElementById('tf-item-count').value;
    }

    if(!confirm("정말 보내시겠습니까? (되돌릴 수 없습니다)")) return;

    try {
        const res = await fetch('api.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data)
        }).then(r=>r.json());
        
        if(res.status=='success') { alert(res.msg); location.reload(); }
        else alert(res.message);
    } catch(e){ alert('오류'); }
}

// 프로필 이미지 변경 (파일 선택창 열기)
function editProfileImage() {
    document.getElementById('profile-upload').click();
}

// 파일 선택 시 자동 업로드
async function uploadProfileImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('cmd', 'update_profile_img_file');
        formData.append('img_file', file);

        try {
            const res = await fetch('api.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        } catch (e) {
            alert('업로드 오류');
        }
    }
}
</script>
</body>
</html>