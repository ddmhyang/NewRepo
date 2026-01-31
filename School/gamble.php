<?php
// gamble.php
require_once 'common.php';
if (!isset($_SESSION['uid'])) { header("Location: index.php"); exit; }
$me = sql_fetch("SELECT point FROM School_Members WHERE id=?", [$_SESSION['uid']]);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>도박장</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --bg: #2C3E50; --white: #fff; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: white; text-align: center; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .my-point { background: rgba(0,0,0,0.5); padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .tab { flex: 1; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.2s; font-size: 14px; }
        .tab.active { background: var(--primary); color: white; transform: scale(1.05); }

        .game-area { background: white; color: #333; border-radius: 20px; padding: 20px; min-height: 350px; display: none; flex-direction: column; justify-content: center; align-items: center; }
        .game-area.active { display: flex; }

        input, select { padding: 15px; font-size: 18px; width: 100%; box-sizing: border-box; border-radius: 10px; border: 2px solid #ddd; text-align: center; margin-bottom: 10px; font-family: 'Pretendard'; }
        .btn-go { width: 100%; padding: 15px; font-size: 18px; font-weight: bold; background: #2c3e50; color: white; border: none; border-radius: 10px; cursor: pointer; }

        /* 룰렛 등 스타일 유지 */
        .roulette-wheel { width: 150px; height: 150px; border-radius: 50%; border: 8px solid var(--primary); display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--primary); margin-bottom: 20px; background: #f9f9f9; }
        .animate-spin { animation: spin 0.5s infinite linear; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        /* 홀짝 & 블랙잭 스타일 생략 (기존 유지) */
        .hj-btns { display: flex; gap: 15px; width: 100%; margin: 20px 0; }
        .hj-btn { flex: 1; padding: 30px; border-radius: 15px; border: 2px solid #ddd; font-size: 24px; font-weight: 800; cursor: pointer; }
        .hj-odd { background: #3498db; color: white; } .hj-even { background: #e74c3c; color: white; }
        .cards { display: flex; justify-content: center; gap: 5px; min-height: 60px; margin-top: 5px; }
        .card { width: 40px; height: 56px; background: white; border-radius: 4px; color: #333; font-weight: bold; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 1px 1px 3px rgba(0,0,0,0.3); }
        .card.red { color: #e74c3c; } .card.hidden { background: #34495e; color: transparent; border: 1px solid #fff; }
        .bj-controls { display: flex; gap: 10px; width: 100%; margin-top: 15px; }
        .btn-hit { flex: 1; background: #2980b9; color: white; } .btn-stand { flex: 1; background: #c0392b; color: white; }
        .btn-hit, .btn-stand { padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .bj-table { width: 100%; background: #27ae60; border-radius: 15px; padding: 20px; color: white; }
    </style>
</head>
<body>

<div class="header">
    <div onclick="location.href='index.php'" style="cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></div>
    <div class="my-point"><i class="fa-solid fa-coins"></i> <span id="ui-point"><?=number_format($me['point'])?></span> P</div>
</div>

<div class="tabs">
    <div class="tab active" onclick="setTab('odd-even')">홀짝</div>
    <div class="tab" onclick="setTab('roulette')">룰렛</div>
    <div class="tab" onclick="setTab('blackjack')">블랙잭</div>
</div>

<div id="game-odd-even" class="game-area active">
    <h2>홀 vs 짝</h2>
    <p style="color:#666; margin-bottom:20px;">배팅금의 2배</p>
    <input type="number" id="bet-hj" placeholder="배팅할 포인트">
    <div class="hj-btns">
        <div class="hj-btn hj-odd" onclick="playHJ('odd')">홀</div>
        <div class="hj-btn hj-even" onclick="playHJ('even')">짝</div>
    </div>
</div>

<div id="game-roulette" class="game-area">
    <h2>행운의 룰렛</h2>
    <div class="roulette-wheel" id="wheel-icon"><i class="fa-solid fa-arrows-spin"></i></div>
    <p id="roulette-result" style="font-weight:bold; font-size:18px; min-height: 27px;">...</p>
    
    <select id="roulette-type">
        <option value="기본">기본 룰렛</option>
    </select>
    
    <input type="number" id="bet-rl" placeholder="배팅할 포인트">
    <button class="btn-go" onclick="playRoulette()">돌리기!</button>
</div>

<div id="game-blackjack" class="game-area">
    <div id="bj-start-screen" style="width:100%; text-align:center;">
        <h2>Blackjack</h2>
        <p style="color:#666;">딜러를 이기면 2배!</p>
        <input type="number" id="bet-bj" placeholder="배팅할 포인트">
        <button class="btn-go" onclick="startBJ()">게임 시작</button>
    </div>
    <div id="bj-play-screen" style="display:none; width:100%;">
        <div class="bj-table">
            <div style="text-align:center; margin-bottom:10px;">
                <span style="font-size:12px;">DEALER</span>
                <div class="cards" id="dealer-cards"></div>
                <div style="font-weight:bold; color:#f1c40f;" id="dealer-score">?</div>
            </div>
            <div style="text-align:center;">
                <span style="font-size:12px;">YOU</span>
                <div class="cards" id="player-cards"></div>
                <div style="font-weight:bold; color:#f1c40f;" id="player-score">0</div>
            </div>
        </div>
        <div class="bj-controls">
            <button class="btn-hit" onclick="actionBJ('hit')">HIT</button>
            <button class="btn-stand" onclick="actionBJ('stand')">STAND</button>
        </div>
    </div>
</div>

<script>
// 페이지 로드 시 룰렛 종류 가져오기
window.onload = async function() {
    await loadRouletteTypes();
};

async function loadRouletteTypes() {
    try {
        const res = await api({ cmd: 'get_roulette_types' });
        if(res.status === 'success' && res.list.length > 0) {
            const sel = document.getElementById('roulette-type');
            sel.innerHTML = '';
            res.list.forEach(type => {
                const opt = document.createElement('option');
                opt.value = type;
                opt.textContent = type + " 룰렛";
                sel.appendChild(opt);
            });
        }
    } catch(e) {}
}

function setTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.game-area').forEach(g => g.classList.remove('active'));
    
    if(name === 'odd-even') {
        document.querySelectorAll('.tab')[0].classList.add('active');
        document.getElementById('game-odd-even').classList.add('active');
    } else if(name === 'roulette') {
        document.querySelectorAll('.tab')[1].classList.add('active');
        document.getElementById('game-roulette').classList.add('active');
    } else {
        document.querySelectorAll('.tab')[2].classList.add('active');
        document.getElementById('game-blackjack').classList.add('active');
    }
}

// API 함수
async function api(data) {
    return await fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json());
}

// 포인트 업데이트 UI
function updatePoint(pt) {
    if(pt !== undefined) document.getElementById('ui-point').textContent = Number(pt).toLocaleString();
}

// --- 게임 로직 ---

async function playHJ(pick) {
    const point = document.getElementById('bet-hj').value;
    if(!point || point <= 0) return alert("배팅금을 입력하세요.");
    try {
        const res = await api({ cmd: 'gamble_hj', amount: point, pick: pick });
        if(res.status === 'win') alert(`🎉 적중! ${res.gain} P 획득!`);
        else if(res.status === 'lose') alert(`😭 땡! 결과: [${res.result}]`);
        else alert(res.message);
        updatePoint(res.current_point);
    } catch(e) { alert('오류'); }
}

async function playRoulette() {
    const point = document.getElementById('bet-rl').value;
    const type = document.getElementById('roulette-type').value;
    if(!point || point <= 0) return alert("배팅금을 입력하세요.");
    
    const wheel = document.getElementById('wheel-icon');
    const resultText = document.getElementById('roulette-result');
    wheel.classList.add('animate-spin');
    resultText.textContent = "돌아가는 중...";
    
    try {
        const res = await api({ cmd: 'gamble_roulette', amount: point, type: type });
        setTimeout(() => {
            wheel.classList.remove('animate-spin');
            if(res.status === 'success') {
                const data = res.data;
                const gain = res.gain; 
                let msg = "";
                
                // 결과 표시 로직
                if(data.ratio > 0) {
                    msg = `축하합니다! ${data.name} (x${data.ratio})`;
                    resultText.innerHTML = `<span style='color:red'>🎉 ${data.name} (+${gain})</span>`;
                } else if(data.ratio == 0) {
                    msg = `꽝... ${data.name} (0배)`;
                    resultText.innerHTML = `<span style='color:gray'>☁️ ${data.name}</span>`;
                } else {
                    msg = `저런... ${data.name} (x${data.ratio})`;
                    resultText.innerHTML = `<span style='color:blue'>⚡ ${data.name} (${gain})</span>`;
                }
                alert(msg);
                updatePoint(res.current_point);
            } else { alert(res.message); }
        }, 1500);
    } catch(e) { wheel.classList.remove('animate-spin'); }
}

async function startBJ() {
    const point = document.getElementById('bet-bj').value;
    if(!point || point <= 0) return alert("배팅금을 입력하세요.");
    try {
        const res = await api({ cmd: 'gamble_bj_start', amount: point });
        if(res.status === 'success') {
            document.getElementById('bj-start-screen').style.display = 'none';
            document.getElementById('bj-play-screen').style.display = 'block';
            updateBJTable(res.data);
            updatePoint(res.current_point);
        } else alert(res.message);
    } catch(e) {}
}

async function actionBJ(act) {
    try {
        const res = await api({ cmd: 'gamble_bj_action', action: act });
        if(res.status === 'playing') updateBJTable(res.data);
        else if (res.status === 'end') {
            updateBJTable(res.data, true);
            setTimeout(() => {
                alert(res.msg);
                document.getElementById('bj-start-screen').style.display = 'block';
                document.getElementById('bj-play-screen').style.display = 'none';
                updatePoint(res.current_point);
            }, 500);
        }
    } catch(e) {}
}

function updateBJTable(data, showDealer=false) {
    const pBox = document.getElementById('player-cards');
    pBox.innerHTML = '';
    data.player_hand.forEach(c => pBox.innerHTML += makeCardHtml(c));
    document.getElementById('player-score').textContent = data.player_score;

    const dBox = document.getElementById('dealer-cards');
    dBox.innerHTML = '';
    data.dealer_hand.forEach((c, i) => {
        if (i === 0 && !showDealer) dBox.innerHTML += `<div class="card hidden">?</div>`;
        else dBox.innerHTML += makeCardHtml(c);
    });
    document.getElementById('dealer-score').textContent = showDealer ? data.dealer_score : '?';
}
function makeCardHtml(val) {
    let display = val;
    if(val===1) display='A'; if(val===11) display='J'; if(val===12) display='Q'; if(val===13) display='K';
    const isRed = Math.random()>0.5 ? 'red' : '';
    return `<div class="card ${isRed}">${display}</div>`;
}
</script>
</body>
</html>