<?php
// battle.php : 포켓몬 스타일 (UI 개선: 글자 확대, HP수치 표시)
require_once 'common.php';
if (!isset($_SESSION['uid'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BATTLE</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --bg: #2C3E50; --chat-bg: #fff; --white: #fff; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; color: white; overflow: hidden; height: 100vh; display: flex; flex-direction: column; }
        
        /* 상단 HUD */
        .hud-top { padding: 15px; background: rgba(0,0,0,0.3); display: flex; justify-content: space-between; align-items: center; }
        .hp-bar-box { width: 150px; height: 18px; background: #555; border-radius: 10px; overflow: hidden; position: relative; border: 2px solid white; }
        .hp-fill { height: 100%; background: #2ecc71; width: 100%; transition: width 0.3s; }
        
        /* 몬스터 필드 */
        .battle-field { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 20px; position: relative; }
        .mob-sprite { font-size: 100px; color: #e74c3c; animation: float 2s infinite ease-in-out; text-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        
        .mob-info { background: rgba(0,0,0,0.6); padding: 15px 25px; border-radius: 20px; text-align: center; backdrop-filter: blur(5px); }
        .mob-hp-bar { width: 220px; height: 12px; background: #333; margin-top: 8px; border-radius: 6px; overflow: hidden; margin-bottom: 5px; }
        .mob-hp-fill { height: 100%; background: #e74c3c; width: 100%; transition: width 0.3s; }
        .mob-hp-txt { font-size: 14px; font-weight: bold; color: #fff; letter-spacing: 1px; }

        /* 하단 패널 */
        .bottom-panel { background: white; border-top-left-radius: 25px; border-top-right-radius: 25px; display: flex; flex-direction: column; height: 50%; max-height: 450px; color: #333; box-shadow: 0 -5px 20px rgba(0,0,0,0.2); }
        
        /* 로그 창 (글자 크기 확대) */
        .log-window { 
            flex: 1; padding: 25px; overflow-y: auto; background: #fff; 
            border-bottom: 1px solid #eee; border-top-left-radius: 25px; border-top-right-radius: 25px;
            font-size: 18px; line-height: 1.6; color: #333;
        }
        .log-item { margin-bottom: 12px; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* 컨트롤 버튼 */
        .control-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 20px; background: #f8f9fa; }
        .btn-act { padding: 25px 0; border: none; border-radius: 15px; font-size: 20px; font-weight: 800; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 0 rgba(0,0,0,0.1); transition: 0.1s; }
        .btn-act:active { transform: translateY(4px); box-shadow: none; }
        
        .btn-atk { background: #e74c3c; grid-column: 1 / 2; }
        .btn-bag { background: #f39c12; grid-column: 2 / 3; }
        .btn-run { background: #95a5a6; grid-column: 1 / 3; }

        /* 방어 버튼 */
        .def-controls { display: none; grid-template-columns: 1fr 1fr 1fr; gap: 8px; padding: 20px; background: #fff5f5; }
        .btn-def { padding: 20px 0; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; color: white; font-size: 18px; }
        .btn-counter { background: #e67e22; }
        .btn-dodge { background: #3498db; }
        .btn-hit { background: #7f8c8d; }

        .overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); color:white; display:flex; justify-content:center; align-items:center; z-index:999; display: none; }
    </style>
</head>
<body>

<div class="hud-top">
    <div onclick="location.href='index.php'" style="cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></div>
    <div style="display:flex; align-items:center; gap:10px;">
        <span id="my-name" style="font-weight:bold; font-size:16px;">나</span>
        <div class="hp-bar-box"><div class="hp-fill" id="my-hp-bar"></div></div>
        <span id="my-hp-txt" style="font-size:14px; font-weight:bold;"></span>
    </div>
    
</div>

<div class="battle-field">
    <div class="mob-info">
        <div id="mob-name" style="font-weight:bold; font-size:22px;">???</div>
        <div class="mob-hp-bar"><div class="mob-hp-fill" id="mob-hp-bar"></div></div>
        <div class="mob-hp-txt" id="mob-hp-txt">0 / 0</div>
    </div>
    <div class="mob-sprite"><i class="fa-solid fa-ghost"></i></div>
</div>

<div class="bottom-panel">
    <div class="log-window" id="log-window">
        <div class="log-item">전투 준비 중...</div>
    </div>
    
    <div class="control-grid" id="menu-main">
        <button class="btn-act btn-atk" onclick="Battle.act('attack')">
            <i class="fa-solid fa-gavel"></i> 공격
        </button>
        <button class="btn-act btn-bag" onclick="location.href='inventory.php'">
            <i class="fa-solid fa-briefcase"></i> 가방
        </button>
        <button class="btn-act btn-run" onclick="Battle.act('run')">
            <i class="fa-solid fa-person-running"></i> 도망
        </button>
    </div>

    <div class="def-controls" id="menu-defend">
        <button class="btn-def btn-counter" onclick="Battle.defend('counter')">반격</button>
        <button class="btn-def btn-dodge" onclick="Battle.defend('dodge')">회피</button>
        <button class="btn-def btn-hit" onclick="Battle.defend('hit')">맞기</button>
    </div>
</div>

<div class="overlay" id="start-overlay" style="display:flex;">
    <button onclick="Battle.start()" style="padding:20px 50px; font-size:24px; border-radius:50px; border:none; background:var(--primary); color:white; font-weight:bold; cursor:pointer; box-shadow: 0 10px 20px rgba(0,0,0,0.3);">전투 시작!</button>
</div>

<script>
const Battle = {
    timer: null,
    
    start: async function() {
        try {
            const res = await this.api({ cmd: 'battle_start' });
            if(res.status === 'success') {
                document.getElementById('start-overlay').style.display = 'none';
                this.loop();
            } else { alert(res.message); }
        } catch(e) { alert('오류 발생'); }
    },

    loop: async function() {
        try {
            const res = await this.api({ cmd: 'battle_info' });
            if(res.status === 'playing') {
                this.render(res.data);
                this.timer = setTimeout(() => this.loop(), 1000);
            } 
            else if (res.status === 'ended') {
                alert("전투 종료");
                location.href = 'index.php';
            }
        } catch(e) { console.error(e); }
    },

    render: function(data) {
        const me = data.players_data[0];
        const mob = data.mob_live_data[0];

        document.getElementById('my-name').textContent = me.name;
        document.getElementById('my-hp-txt').textContent = `${me.hp_cur}/${me.hp_max}`;
        document.getElementById('my-hp-bar').style.width = Math.max(0, (me.hp_cur/me.hp_max)*100) + '%';
        
        document.getElementById('mob-name').textContent = mob.name;
        // [변경] 몬스터 HP 수치 업데이트
        document.getElementById('mob-hp-txt').textContent = `${mob.hp_cur} / ${mob.hp_max}`;
        document.getElementById('mob-hp-bar').style.width = Math.max(0, (mob.hp_cur/mob.hp_max)*100) + '%';

        // 로그 업데이트 (시간 제거)
        const logBox = document.getElementById('log-window');
        const oldHtml = logBox.innerHTML;
        let newHtml = '';
        data.battle_log.forEach(l => {
            // [변경] 시간([xx:xx]) 제거하고 메시지만 출력
            newHtml += `<div class="log-item">${l.msg}</div>`;
        });
        
        if (logBox.innerHTML !== newHtml) {
            logBox.innerHTML = newHtml;
            logBox.scrollTop = logBox.scrollHeight;
        }

        const menuMain = document.getElementById('menu-main');
        const menuDef = document.getElementById('menu-defend');

        if (data.turn_status === 'player') {
            menuMain.style.display = 'grid';
            menuDef.style.display = 'none';
        } else if (data.turn_status === 'player_defend') {
            menuMain.style.display = 'none';
            menuDef.style.display = 'grid';
        } else {
            menuMain.style.display = 'none';
            menuDef.style.display = 'none';
        }
    },

    act: async function(type) {
        if (type === 'attack') {
            const res = await this.api({ cmd: 'battle_action_attack' });
            this.handleRes(res);
        } else if (type === 'run') {
            if(!confirm("도망치시겠습니까?")) return;
            const res = await this.api({ cmd: 'battle_run' });
            this.handleRes(res);
        }
    },

    defend: async function(type) {
        const res = await this.api({ cmd: 'battle_action_defend', type: type });
        this.handleRes(res);
    },

    handleRes: function(res) {
        if(res.status === 'win' || res.status === 'lose' || res.status === 'success' || res.status === 'fail') {
            if(res.msg) {
                // 승리/패배 메시지는 alert 대신 화면 렌더링 후 잠시 뒤 이동해도 됨
                // 여기선 일단 alert 처리
            }
            if(res.status === 'win' || res.status === 'lose') {
                alert(res.status === 'win' ? "승리!" : "패배...");
                location.href = 'index.php';
            }
            else this.render(); 
        } else {
            alert(res.message);
        }
    },

    api: async function(data) {
        return await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json());
    }
};
</script>
</body>
</html>