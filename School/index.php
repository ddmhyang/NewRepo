<?php
session_start();
if (!file_exists('config.php')) { header("Location: setup.php"); exit; }
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>School Survival SPA</title>
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
                --point: #AED1D5;
                --bg: #F0F2F5;
                --text: #333;
                --white: #fff;
            }
            body {
                font-family: 'Pretendard', sans-serif;
                background: var(--bg);
                color: var(--text);
                margin: 0;
                height: 100vh;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            /* SPA View 컨테이너 */
            .spa-view {
                display: none;
                width: 100%;
                height: 100%;
                flex-direction: column;
                overflow-y: auto;
            }
            .spa-view.active {
                display: flex;
            }

            /* 헤더 & 공통 */
            header {
                background: var(--primary);
                color: var(--white);
                padding: 15px 20px;
                font-weight: 800;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 4px 15px rgba(206, 89, 97, 0.2);
                position: sticky;
                top: 0;
                z-index: 100;
            }
            .container {
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                box-sizing: border-box;
                flex: 1;
            }

            /* 로그인 */
            .login-wrapper {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 80vh;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                width: 100%;
                max-width: 350px;
                text-align: center;
            }
            input {
                width: 100%;
                padding: 15px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 12px;
                box-sizing: border-box;
            }
            .btn-main {
                width: 100%;
                padding: 15px;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
            }

            /* 프로필 & 메뉴 */
            .profile-card {
                background: linear-gradient(135deg, var(--secondary), var(--primary));
                color: white;
                padding: 25px;
                border-radius: 20px;
                box-shadow: 0 10px 20px rgba(206, 89, 97, 0.2);
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .profile-avatar {
                width: 60px;
                height: 60px;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .profile-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .dashboard-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .menu-card {
                background: white;
                border-radius: 18px;
                padding: 25px 15px;
                text-align: center;
                box-shadow: 0 4px 10px rgba(0,0,0,0.02);
                cursor: pointer;
                transition: 0.2s;
                border: 2px solid transparent;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .menu-card:hover {
                transform: translateY(-5px);
                border-color: var(--point);
            }
            .menu-card i {
                font-size: 32px;
                margin-bottom: 12px;
                color: var(--primary);
            }
            .menu-card span {
                font-weight: 700;
                font-size: 17px;
            }
            .menu-card .sub {
                font-size: 12px;
                color: #999;
                margin-top: 4px;
            }

            /* 전투 화면 스타일 */
            #view-battle {
                background: #2C3E50;
                color: white;
            }
            .battle-header {
                padding: 15px;
                background: rgba(0,0,0,0.3);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .battle-field {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 20px;
                position: relative;
            }
            .mob-sprite {
                font-size: 80px;
                color: #e74c3c;
                animation: float 2s infinite;
                text-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            @keyframes float {
                0%,
                100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-10px);
                }
            }

            .mob-info {
                background: rgba(0,0,0,0.6);
                padding: 15px 25px;
                border-radius: 20px;
                text-align: center;
            }
            .hp-bar {
                width: 200px;
                height: 10px;
                background: #555;
                border-radius: 5px;
                overflow: hidden;
                margin-top: 5px;
            }
            .hp-fill {
                height: 100%;
                background: #e74c3c;
                width: 100%;
                transition: 0.3s;
            }

            .battle-ui-bottom {
                background: white;
                border-top-left-radius: 25px;
                border-top-right-radius: 25px;
                padding: 20px;
                color: #333;
                height: 40%;
                display: flex;
                flex-direction: column;
            }
            .log-box {
                flex: 1;
                overflow-y: auto;
                margin-bottom: 15px;
                font-size: 15px;
                line-height: 1.5;
                border: 1px solid #eee;
                padding: 10px;
                border-radius: 10px;
                background: #f9f9f9;
            }
            .ctrl-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .btn-act {
                padding: 15px;
                border: none;
                border-radius: 10px;
                font-weight: bold;
                cursor: pointer;
                color: white;
                font-size: 16px;
            }

            /* 대기실 */
            .wait-room {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 100%;
                text-align: center;
            }
            .vs-badge {
                background: #e74c3c;
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-weight: bold;
                margin: 20px 0;
            }
            .ready-btn {
                padding: 15px 40px;
                font-size: 20px;
                border-radius: 30px;
                background: #95a5a6;
                color: white;
                border: none;
                cursor: pointer;
                transition: 0.3s;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .ready-btn.active {
                background: #2ecc71;
                transform: scale(1.1);
                box-shadow: 0 0 20px #2ecc71;
            }

            /* 알림 및 모달 */
            #alert-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(231, 76, 60, 0.95);
                z-index: 9999;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                color: white;
            }
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 1000;
                justify-content: center;
                align-items: center;
            }
            .modal-content {
                background: white;
                padding: 25px;
                border-radius: 20px;
                width: 90%;
                max-width: 350px;
                text-align: center;
            }
            .user-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #eee;
            }
            .injury-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: bold;
                margin-top: 5px;
            }
            .inj-0 {
                background: #2ecc71;
                color: white;
            }
            .inj-1 {
                background: #f1c40f;
                color: #000;
            }
            .inj-2 {
                background: #e67e22;
                color: white;
            }
            .inj-3 {
                background: #e74c3c;
                color: white;
            }
            .inj-4 {
                background: #000;
                color: red;
                border: 1px solid red;
            }
        </style>
    </head>
    <body>

        <div id="view-login" class="spa-view">
            <div class="login-wrapper">
                <div class="login-box">
                    <h2 style="color:#CE5961; margin-bottom:20px;">
                        <i class="fa-solid fa-school"></i><br>School RPG</h2>
                    <input type="text" id="l-name" placeholder="이름">
                    <input type="password" id="l-pw" placeholder="비밀번호">
                    <button class="btn-main" onclick="App.login()">접속하기</button>
                </div>
            </div>
        </div>

        <div id="view-lobby" class="spa-view">
            <header>
                <div>
                    <i class="fa-solid fa-graduation-cap"></i>
                    School RPG</div>
                <div onclick="App.logout()" style="cursor:pointer; font-size:13px;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    로그아웃</div>
            </header>

            <div class="container">
                <div class="profile-card">
                    <div>
                        <h1 id="ui-name">로딩 중...</h1>
                        <p id="ui-stat">-</p>
                        <div id="ui-injury"></div>
                    </div>
                    <div class="profile-avatar" id="ui-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div id="menu-admin" style="display:none;">
                    <div
                        style="font-size:14px; font-weight:bold; color:#777; margin-bottom:10px; margin-left:5px;">관리자 패널</div>
                    <div class="dashboard-grid">
                        <div class="menu-card" onclick="location.href='admin_member.php'">
                            <i class="fa-solid fa-users-gear"></i>
                            <span>캐릭터 관리</span>
                        </div>
                        <div class="menu-card" onclick="location.href='admin_item.php'">
                            <i class="fa-solid fa-shirt"></i>
                            <span>아이템 설정</span>
                        </div>
                        <div class="menu-card" onclick="location.href='admin_monster.php'">
                            <i class="fa-solid fa-skull-crossbones"></i>
                            <span>몬스터 설정</span>
                        </div>
                        <div class="menu-card" onclick="location.href='admin_status.php'">
                            <i class="fa-solid fa-flask"></i>
                            <span>상태이상</span>
                        </div>
                        <div class="menu-card" onclick="location.href='admin_gamble.php'">
                            <i class="fa-solid fa-dice"></i>
                            <span>도박장 설정</span>
                        </div>
                        <div class="menu-card" onclick="location.href='admin_battle.php'">
                            <i class="fa-solid fa-server"></i>
                            <span>방 관리</span>
                        </div>
                        <div class="menu-card" onclick="location.href='log.php'">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>전체 로그</span>
                        </div>
                    </div>
                </div>

                <div id="menu-student" style="display:none;">
                    <div
                        style="font-size:14px; font-weight:bold; color:#777; margin-bottom:10px; margin-left:5px;">학교 생활</div>
                    <div class="dashboard-grid">
                        <div class="menu-card" onclick="App.openBattleModal()">
                            <i class="fa-solid fa-hand-fist"></i>
                            <span>싸움</span><span class="sub">탐색/결투</span>
                        </div>
                        <div class="menu-card" onclick="location.href='inventory.php'">
                            <i class="fa-solid fa-briefcase"></i>
                            <span>가방</span><span class="sub">내 소지품</span>
                        </div>
                        <div class="menu-card" onclick="location.href='shop.php'">
                            <i class="fa-solid fa-shop"></i>
                            <span>매점</span><span class="sub">아이템 구매</span>
                        </div>
                        <div class="menu-card" onclick="location.href='gamble.php'">
                            <i class="fa-solid fa-dice-d20"></i>
                            <span>도박장</span><span class="sub">운 시험하기</span>
                        </div>
                        <div class="menu-card" onclick="location.href='log.php'">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>기록</span><span class="sub">활동 내역</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-battle" class="spa-view">
            <div class="battle-header">
                <span>Room
                    <span id="bt-room-id">0</span></span>
                <span
                    onclick="App.exitBattle()"
                    style="cursor:pointer; background:rgba(255,255,255,0.2); padding:5px 10px; border-radius:10px;">나가기</span>
            </div>

            <div id="battle-wait" class="wait-room" style="display:none;">
                <h2 style="margin-bottom:10px;">대기실</h2>
                <div style="color:#aaa; margin-bottom:30px;">준비가 되면 버튼을 누르세요.</div>
                <div style="font-size:20px; font-weight:bold; margin-bottom:50px;">
                    <span id="wait-p1" style="color:#3498db;">나</span>
                    <span class="vs-badge">VS</span>
                    <span id="wait-p2" style="color:#e74c3c;">???</span>
                </div>
                <button id="btn-ready" class="ready-btn" onclick="App.toggleReady()">준비</button>
                <p id="wait-status" style="margin-top:20px; color:#999; font-size:14px;">상대를 기다리는 중...</p>
            </div>

            <div id="battle-play" style="display:none; flex:1; flex-direction:column;">
                <div class="battle-field">
                    <div class="mob-info">
                        <div id="mob-name" style="font-weight:bold; font-size:20px; color:white;">???</div>
                        <div class="hp-bar">
                            <div id="mob-hp" class="hp-fill"></div>
                        </div>
                        <div id="mob-hp-txt" style="font-size:12px; margin-top:3px; color:#ddd;">0 / 0</div>
                    </div>
                    <div class="mob-sprite">
                        <i class="fa-solid fa-ghost"></i>
                    </div>
                </div>
                <div class="battle-ui-bottom">
                    <div
                        style="display:flex; justify-content:space-between; margin-bottom:10px; font-weight:bold;">
                        <span id="my-name">나</span>
                        <span id="my-hp-txt" style="color:#2ecc71;">100/100</span>
                    </div>
                    <div id="bt-log" class="log-box">전투 준비 중...</div>
                    <div id="ctrl-main" class="ctrl-grid">
                        <button class="btn-act" style="background:#e74c3c;" onclick="App.act('attack')">공격</button>
                        <button
                            class="btn-act"
                            style="background:#f39c12;"
                            onclick="location.href='inventory.php'">가방</button>
                        <button
                            class="btn-act"
                            style="background:#95a5a6; grid-column:span 2;"
                            onclick="App.act('run')">도망치기</button>
                    </div>
                    <div id="ctrl-def" class="ctrl-grid" style="display:none;">
                        <button
                            class="btn-act"
                            style="background:#e67e22;"
                            onclick="App.defend('counter')">반격</button>
                        <button
                            class="btn-act"
                            style="background:#3498db;"
                            onclick="App.defend('dodge')">회피</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="alert-overlay">
            <h2 style="margin-bottom:20px; animation:blink 1s infinite;">⚠️ 결투 신청!</h2>
            <p style="font-size:18px; margin-bottom:30px;">
                <span id="chal-sender" style="color:#f1c40f; font-weight:bold;">???</span>님이 싸움을 걸어왔습니다.</p>
            <div style="display:flex; gap:20px;">
                <button
                    onclick="App.rejectChallenge()"
                    style="padding:10px 20px; border-radius:10px; border:2px solid white; background:transparent; color:white; font-weight:bold;">무시</button>
                <button
                    onclick="App.acceptChallenge()"
                    style="padding:10px 20px; border-radius:10px; border:none; background:white; color:#c0392b; font-weight:bold;">수락</button>
            </div>
        </div>

        <div
            id="battle-modal"
            class="modal-overlay"
            onclick="if(event.target==this) App.closeModals()">
            <div class="modal-content">
                <h3>⚔️ 싸움 방식 선택</h3>
                <button
                    class="btn-main"
                    style="background:#2ecc71; margin-bottom:10px;"
                    onclick="App.startPvE()">
                    <b>🌲 학교 탐색</b><br>
                    <small>몬스터와 싸웁니다.</small>
                </button>
                <button
                    class="btn-main"
                    style="background:#e74c3c;"
                    onclick="App.openUserList()">
                    <b>🤬 유저와 다툼</b><br>
                    <small>상대를 지목합니다.</small>
                </button>
            </div>
        </div>

        <div
            id="user-list-modal"
            class="modal-overlay"
            onclick="if(event.target==this) App.closeModals()">
            <div class="modal-content" style="max-height:80vh; overflow-y:auto;">
                <h3>시비 걸 상대 선택</h3>
                <div id="user-list-box">로딩 중...</div>
                <button
                    class="btn-main"
                    style="margin-top:15px; background:#999;"
                    onclick="App.closeModals()">닫기</button>
            </div>
        </div>

        <script>
// index.php 하단의 <script> 태그 내부 내용을 이걸로 교체하세요.

const App = {
    roomId: 0,
    myId: 0,
    isReady: false,
    challengeId: 0,

    init() {
        this.poll();
        setInterval(() => this.poll(), 1000);
    },

    async api(data) {
        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            return await res.json();
        } catch (e) {
            console.error(e);
            return { status: 'error', message: '통신 오류가 발생했습니다.' };
        }
    },

    async poll() {
        // poll에서 에러가 나도 멈추지 않도록 예외 처리
        const res = await this.api({ cmd: 'get_my_info' });
        
        if (res.status === 'error' || !res.data) {
            // 로그인 정보가 없으면 로그인 화면으로
            this.showView('login');
            return;
        }

        this.myId = res.data.id;
        this.updateLobby(res.data);

        // 1. 결투 알림
        if (res.challenge) {
            document.getElementById('alert-overlay').style.display = 'flex';
            document.getElementById('chal-sender').innerText = res.challenge.name;
            this.challengeId = res.challenge.room_id;
        } else {
            document.getElementById('alert-overlay').style.display = 'none';
        }

        // 2. 전투 방 상태 확인
        if (res.active_room) {
            this.roomId = res.active_room.room_id;
            this.showView('battle');
            document.getElementById('bt-room-id').innerText = this.roomId;

            if (res.active_room.status === 'FIGHTING') {
                this.refreshBattle();
                this.switchBattleMode('play');
            } else {
                this.refreshWaitRoom();
                this.switchBattleMode('wait');
            }
        } else {
            // 전투 중이 아닌데 전투 화면에 있다면 로비로 이동
            const isBattleView = document.getElementById('view-battle').classList.contains('active');
            const isNoView = !document.querySelector('.spa-view.active');
            
            if (isBattleView || isNoView) {
                this.showView('lobby');
            }
        }
    },

    // UI 헬퍼 함수들
    updateLobby(me) {
        document.getElementById('ui-name').textContent = me.name;
        
        // 관리자/학생 메뉴 분기
        if(me.role === 'admin') {
            document.getElementById('ui-stat').textContent = "관리자 권한";
            document.getElementById('menu-admin').style.display = 'grid';
            document.getElementById('menu-student').style.display = 'none';
        } else {
            document.getElementById('ui-stat').textContent = `Lv.${me.level} | ${Number(me.point).toLocaleString()} P`;
            document.getElementById('menu-admin').style.display = 'none';
            document.getElementById('menu-student').style.display = 'grid';
            
            const inj = parseInt(me.injury || 0);
            const injNames = ["정상", "경상", "중상", "위독", "사망"];
            const injHtml = `<span class="injury-badge inj-${inj}">상태: ${injNames[inj]}</span>`;
            document.getElementById('ui-injury').innerHTML = injHtml;
        }
        
        if (me.img_profile) {
            document.getElementById('ui-avatar').innerHTML = `<img src="${me.img_profile}">`;
        }
    },

    showView(name) {
        document.querySelectorAll('.spa-view').forEach(el => el.classList.remove('active'));
        document.getElementById('view-' + name).classList.add('active');
    },

    switchBattleMode(mode) {
        document.getElementById('battle-wait').style.display = (mode === 'wait') ? 'flex' : 'none';
        document.getElementById('battle-play').style.display = (mode === 'play') ? 'flex' : 'none';
    },

    // --- 액션 로직 ---

    async login() {
        const name = document.getElementById('l-name').value;
        const pw = document.getElementById('l-pw').value;
        const res = await this.api({ cmd: 'login', name, pw });
        if (res.status === 'success') {
            this.poll();
        } else {
            alert(res.message);
        }
    },

    async logout() {
        await this.api({ cmd: 'logout' });
        location.reload();
    },

    openBattleModal() { document.getElementById('battle-modal').style.display = 'flex'; },
    
    closeModals() {
        document.getElementById('battle-modal').style.display = 'none';
        document.getElementById('user-list-modal').style.display = 'none';
    },

    // [수정] 학교 탐색 (PVE)
    async startPvE() {
        const res = await this.api({ cmd: 'battle_make_room' });
        if (res.status === 'success') {
            this.closeModals();
            await this.poll(); // 즉시 상태 갱신하여 화면 전환
        } else {
            alert("오류: " + res.message);
        }
    },

    // [수정] 유저 목록 열기 (로딩 멈춤 해결)
    async openUserList() {
        document.getElementById('battle-modal').style.display = 'none';
        document.getElementById('user-list-modal').style.display = 'flex';
        const box = document.getElementById('user-list-box');
        box.innerHTML = '<div style="padding:20px;">목록을 불러오는 중...</div>';

        const res = await this.api({ cmd: 'battle_list_users' });
        
        if (res.status === 'success') {
            let html = '';
            if (res.list.length === 0) {
                html = '<div style="padding:20px; color:#999;">현재 접속 중인(5분 이내) 다른 유저가 없습니다.</div>';
            } else {
                res.list.forEach(u => {
                    html += `
                    <div class="user-item">
                        <div style="text-align:left;">
                            <b>${u.name}</b> (Lv.${u.level})<br>
                            <small style="color:#aaa;">상태: ${u.injury}/4</small>
                        </div>
                        <button onclick="App.challengeUser(${u.id}, '${u.name}')" 
                                style="background:#e74c3c; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:bold;">
                            도전
                        </button>
                    </div>`;
                });
            }
            box.innerHTML = html;
        } else {
            // 에러 발생 시 메시지 출력
            box.innerHTML = `<div style="color:red; padding:20px;">불러오기 실패!<br>${res.message}</div>`;
        }
    },

    async challengeUser(tid, name) {
        if (!confirm(`${name}님에게 결투를 신청하시겠습니까?`)) return;
        const res = await this.api({ cmd: 'battle_challenge', target_id: tid });
        if (res.status === 'success') {
            alert(res.msg);
            this.closeModals();
            this.poll();
        } else {
            alert(res.message);
        }
    },

    async acceptChallenge() {
        const res = await this.api({ cmd: 'battle_join', room_id: this.challengeId });
        if(res.status === 'success') {
            document.getElementById('alert-overlay').style.display = 'none';
            await this.poll();
        } else {
            alert(res.message);
        }
    },

    rejectChallenge() {
        document.getElementById('alert-overlay').style.display = 'none';
    },

    // --- 대기실 및 전투 ---

    async refreshWaitRoom() {
        const res = await this.api({ cmd: 'battle_room_info' });
        if (res.status !== 'success') return;

        document.getElementById('wait-p1').innerText = res.host_name;
        document.getElementById('wait-p2').innerText = res.guest_name;
        
        const msg = document.getElementById('wait-status');
        const btn = document.getElementById('btn-ready');

        // 메시지 및 버튼 상태 설정
        if (res.room.host_id == this.myId) {
            // 내가 방장일 때
            if (res.room.target_id == 0) msg.innerText = "탐색 준비 완료. 준비 버튼을 누르세요.";
            else if (res.room.guest_id > 0) msg.innerText = "상대가 입장했습니다. 준비하세요.";
            else msg.innerText = "상대의 수락을 기다리는 중...";
            
            // 내 준비 상태 확인 (host_ready)
            this.isReady = (res.room.host_ready == 1);
        } else {
            // 내가 게스트일 때
            msg.innerText = "방에 입장했습니다. 준비하세요.";
            
            // 내 준비 상태 확인 (guest_ready)
            this.isReady = (res.room.guest_ready == 1);
        }

        // 버튼 스타일 업데이트
        if (this.isReady) {
            btn.classList.add('active');
            btn.innerText = "준비 완료!";
        } else {
            btn.classList.remove('active');
            btn.innerText = "준비";
        }
    },

    async toggleReady() {
        // 현재 상태 반전해서 전송
        const nextState = !this.isReady;
        const res = await this.api({ cmd: 'battle_ready', ready: nextState });
        if(res.status === 'success') {
            await this.poll();
        } else {
            alert(res.message);
        }
    },

    async refreshBattle() {
        const res = await this.api({ cmd: 'battle_refresh' });
        
        // 전투 종료 처리
        if (res.status === 'end') {
            alert(res.win ? "🏆 승리!" : "💀 패배/종료");
            await this.exitBattle();
            return;
        }
        if (res.status !== 'battle') return;

        // 몬스터 정보
        const enemy = res.enemies[0];
        if (enemy) {
            document.getElementById('mob-name').innerText = enemy.name;
            const pct = (enemy.hp_cur / enemy.hp_max) * 100;
            document.getElementById('mob-hp').style.width = Math.max(0, pct) + '%';
            document.getElementById('mob-hp-txt').innerText = `${enemy.hp_cur} / ${enemy.hp_max}`;
        }

        // 내 정보
        const me = res.players.find(p => p.id == this.myId);
        if (me) {
            document.getElementById('my-name').innerText = me.name;
            document.getElementById('my-hp-txt').innerText = `${me.hp_cur} / ${me.hp_max}`;
        }

        // 턴 제어 UI
        const turn = res.room.turn_status;
        document.getElementById('ctrl-main').style.display = (turn === 'player') ? 'grid' : 'none';
        document.getElementById('ctrl-def').style.display = (turn === 'player_defend') ? 'grid' : 'none';

        // 로그 출력
        const logBox = document.getElementById('bt-log');
        let html = '';
        res.logs.forEach(l => {
            const c = l.type === 'system' ? '#f39c12' : (l.type === 'player' ? '#2ecc71' : '#e74c3c');
            html += `<div style="color:${c}; margin-bottom:4px;">${l.msg}</div>`;
        });
        
        // 로그가 다를 때만 업데이트 (스크롤 튀는 현상 방지)
        if (logBox.innerHTML !== html) {
            logBox.innerHTML = html;
            logBox.scrollTop = logBox.scrollHeight;
        }
    },

    async act(type) {
        if (type === 'run' && !confirm('도망치시겠습니까?')) return;
        const cmd = (type === 'run') ? 'battle_run' : 'battle_action_attack'; // battle_run은 api.php에 구현되어 있어야 함. 없으면 exit 사용
        
        // api.php에 battle_run이 없다면 battle_exit로 대체
        const finalCmd = (cmd === 'battle_run') ? 'battle_exit' : cmd; 
        
        await this.api({ cmd: finalCmd, room_id: this.roomId });
        this.refreshBattle();
    },

    async defend(type) {
        await this.api({ cmd: 'battle_action_defend', type });
        this.refreshBattle();
    },

    async exitBattle() {
        await this.api({ cmd: 'battle_exit' });
        this.isReady = false;
        document.getElementById('btn-ready').classList.remove('active');
        await this.poll();
    }
};

window.onload = () => App.init();
        </script>
    </body>
</html>