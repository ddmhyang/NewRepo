<?php
// admin_status.php : 상태이상 도감 (단계별 설정 기능 포함)
require_once 'common.php';

if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// 1. 저장 (생성/수정)
if (isset($_POST['action']) && ($_POST['action'] === 'create' || $_POST['action'] === 'update')) {
    $name = trim($_POST['name']);
    $max_stage = to_int($_POST['max_stage']);
    
    // 단계별 설정 JSON 조합
// 단계별 설정 JSON 조합
    $stage_config = [];
    for ($i=1; $i<$max_stage; $i++) {
        $stage_config[$i] = [
            'time' => to_int($_POST["stage_time_$i"]),
            'desc' => $_POST["stage_desc_$i"] ?? '',
            'atk'  => to_int($_POST["stage_atk_$i"]),
            'def'  => to_int($_POST["stage_def_$i"]),
            // [추가]
            'cut_hp'    => to_int($_POST["stage_cut_hp_$i"]),
            'cut_point' => to_int($_POST["stage_cut_point_$i"]),
        ];
    }
    $json_config = json_encode($stage_config, JSON_UNESCAPED_UNICODE);

    if ($_POST['action'] === 'create') {
        sql_exec("INSERT INTO School_Status_Info (name, max_stage, stage_config, effect_script) VALUES (?, ?, ?, '{}')", 
            [$name, $max_stage, $json_config]
        );
    } else {
        $id = to_int($_POST['status_id']);
        sql_exec("UPDATE School_Status_Info SET name=?, max_stage=?, stage_config=? WHERE status_id=?", 
            [$name, $max_stage, $json_config, $id]
        );
    }
    echo "<script>location.replace('admin_status.php');</script>";
    exit;
}

// 2. 삭제
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = to_int($_POST['id']);
    sql_exec("DELETE FROM School_Status_Info WHERE status_id=?", [$id]);
    sql_exec("DELETE FROM School_Status_Active WHERE status_id=?", [$id]); // 걸린 사람들도 해제
    echo "<script>location.replace('admin_status.php');</script>";
    exit;
}

$list = sql_fetch_all("SELECT * FROM School_Status_Info ORDER BY status_id DESC");
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>상태이상 도감</title>
        <link
            rel="stylesheet"
            as="style"
            crossorigin="crossorigin"
            href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css"/>
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary: #CE5961;
                --secondary: #D67F85;
                --bg: #F0F2F5;
            }
            body {
                font-family: 'Pretendard', sans-serif;
                background: var(--bg);
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                border-radius: 16px;
                padding: 30px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            }

            h2 {
                margin-top: 0;
                color: #333;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .back-btn {
                background: #eee;
                border: none;
                padding: 8px 15px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
            }

            .form-box {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                border: 1px solid #eee;
            }
            button,
            input {
                padding: 10px;
                margin: 5px 0;
                width: 100%;
                box-sizing: border-box;
                border-radius: 5px;
                border: 1px solid #ddd;
            }
            button {
                border: none;
                cursor: pointer;
                font-weight: bold;
            }
            .stage-row {
                display: flex;
                gap: 10px;
                margin-bottom: 5px;
                align-items: center;
            }

            .list-item {
                border-bottom: 1px solid #eee;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .btn-edit {
                width: auto;
                background: #AED1D5;
                color: #333;
            }
            .btn-del {
                width: auto;
                background: #eee;
                color: #e74c3c;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="color:#CE5961;">
                    <i class="fa-solid fa-biohazard"></i>
                    상태이상 도감</h2>
                <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
            </div>

            <div class="form-box">
                <form method="POST" id="status-form">
                    <input type="hidden" name="action" value="create" id="form-action">
                    <input type="hidden" name="status_id" id="form-id">

                    <label style="font-weight:bold; font-size:14px; color:#555;">상태이상 이름</label>
                    <input
                        type="text"
                        name="name"
                        id="inp-name"
                        required="required"
                        placeholder="예: 좀비 바이러스">

                    <label style="font-weight:bold; font-size:14px; color:#555;">최대 단계 (Max Stage)</label>
                    <input
                        type="number"
                        name="max_stage"
                        id="inp-max"
                        value="3"
                        onchange="renderStages()"
                        min="1">

                    <div
                        id="stage-inputs"
                        style="margin-top:15px; padding:10px; background:white; border-radius:8px; border:1px dashed #ccc;"></div>

                    <button
                        type="submit"
                        style="background:#CE5961; color:white; margin-top:15px;"
                        id="btn-submit">등록하기</button>
                    <button
                        type="button"
                        onclick="resetForm()"
                        style="background:#999; color:white; display:none;"
                        id="btn-cancel">취소</button>
                </form>
            </div>

            <div style="font-size:14px; color:#666; margin-bottom:10px;">등록된 상태이상 목록</div>
            <?php foreach($list as $row): ?>
            <div class="list-item">
                <div>
                    <b style="font-size:16px;"><?=$row['name']?></b>
                    <span style="font-size:13px; color:#777; margin-left:5px;">(최대
                        <?=$row['max_stage']?>단계)</span>
                </div>
                <div>
                    <button
                        type="button"
                        onclick='editStatus(<?=json_encode($row)?>)'
                        class="btn-edit">수정</button>
                    <form
                        method="POST"
                        style="display:inline;"
                        onsubmit="return confirm('정말 삭제합니까?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=$row['status_id']?>">
                        <button type="submit" class="btn-del">삭제</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(!$list) echo "<div style='text-align:center; padding:30px; color:#999;'>등록된 상태이상이 없습니다.</div>"; ?>
        </div>

        <script>
            function renderStages(config = {}) {
                const max = parseInt(document.getElementById('inp-max').value);
                const box = document.getElementById('stage-inputs');

                if (max <= 1) {
                    box.innerHTML = '<p style="font-size:12px; color:#999; text-align:center;">1단계는 진화하지 않습니다.</p>';
                    return;
                }

                let html = '<p style="font-size:12px; color:#666; margin-top:0;">각 단계에서 다음 단계로 가기까지 걸리는 시간' +
                        '(초)과 설명을 입력하세요.</p>';

                for (let i = 1; i < max; i++) {
                    const time = (config && config[i])
                        ? config[i].time
                        : 300;
                    const desc = (config && config[i])
                        ? config[i].desc
                        : '';
                    // [추가] 기존 설정값 가져오기 (없으면 0)
                    const atk = (config && config[i] && config[i].atk)
                        ? config[i].atk
                        : 0;
                    const def = (config && config[i] && config[i].def)
                        ? config[i].def
                        : 0;

                    html += `
            <div class="stage-row" style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px; display:block;">
                <div style="margin-bottom:5px;">
                    <span style="font-weight:bold; color:#CE5961;">${i}단계</span>
                    <input type="number" name="stage_time_${i}" placeholder="진화 시간(초)" value="${time}" style="width:100px;"> 초 후 진화
                </div>
                <div style="display:flex; gap:5px; margin-bottom:5px;">
                    <input type="number" name="stage_atk_${i}" placeholder="ATK 증감" value="${atk}" style="flex:1;">
                    <input type="number" name="stage_def_${i}" placeholder="DEF 증감" value="${def}" style="flex:1;">
                    <input type="number" name="stage_cut_hp_${i}" placeholder="진화시 HP감소" value="${ (
                        config[i]
                            ?.cut_hp || 0
                    )}" style="flex:1; border-color:#e74c3c;">
                    <input type="number" name="stage_cut_point_${i}" placeholder="진화시 포인트차감" value="${ (
                        config[i]
                            ?.cut_point || 0
                    )}" style="flex:1; border-color:#f1c40f;">
                </div>
                <input type="text" name="stage_desc_${i}" placeholder="효과 설명 텍스트 (예: 몸이 굳어 공격력이 감소합니다)" value="${desc}" style="width:100%;">
            </div>`;
                }
                box.innerHTML = html;
            }

            function editStatus(data) {
                document
                    .getElementById('form-action')
                    .value = 'update';
                document
                    .getElementById('form-id')
                    .value = data.status_id;
                document
                    .getElementById('inp-name')
                    .value = data.name;
                document
                    .getElementById('inp-max')
                    .value = data.max_stage;

                document
                    .getElementById('btn-submit')
                    .textContent = '수정사항 저장';
                document
                    .getElementById('btn-cancel')
                    .style
                    .display = 'inline-block';

                let config = {};
                try {
                    config = JSON.parse(data.stage_config);
                } catch (e) {}
                renderStages(config);

                window.scrollTo({top: 0, behavior: 'smooth'});
            }

            function resetForm() {
                document
                    .getElementById('form-action')
                    .value = 'create';
                document
                    .getElementById('form-id')
                    .value = '';
                document
                    .getElementById('status-form')
                    .reset();
                document
                    .getElementById('inp-max')
                    .value = 3;

                document
                    .getElementById('btn-submit')
                    .textContent = '등록하기';
                document
                    .getElementById('btn-cancel')
                    .style
                    .display = 'none';

                renderStages();
            }

            // 초기 실행
            renderStages();
        </script>
    </body>
</html>