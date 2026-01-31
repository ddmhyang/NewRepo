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

            /* --- Mafia Chat Style --- */
        :root {
            --pri: #9575cd; --pri-dark: #7e57c2; --acc: #e57373; 
            --bg: #f3e5f5; --chat-bg: #ede7f6; --me: #d1c4e9; 
        }

        /* 전투 화면 전체 레이아웃 (채팅앱처럼) */
        #view-battle {
            display: flex; flex-direction: column; height: 100vh; background: var(--bg);
            font-family: 'Pretendard', sans-serif;
        }

        /* 헤더 */
        .battle-header {
            padding: 15px; background: white; border-bottom: 1px solid #e1bee7;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 10;
        }
        .battle-title { font-weight: 900; color: var(--pri-dark); font-size: 18px; }

        /* 채팅 영역 (핵심) */
        .chat-area {
            flex: 1; overflow-y: auto; padding: 20px; 
            background: var(--chat-bg); display: flex; flex-direction: column; gap: 15px;
        }

        /* 메시지 버블 스타일 */
        .msg-row { display: flex; align-items: flex-end; gap: 10px; max-width: 85%; }
        .msg-row.me { align-self: flex-end; flex-direction: row-reverse; }
        .msg-row.system { align-self: center; max-width: 90%; justify-content: center; margin: 10px 0; }

        .msg-profile { 
            width: 40px; height: 40px; border-radius: 14px; 
            background: #ddd; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .msg-profile img { width: 100%; height: 100%; object-fit: cover; }

        .msg-content { display: flex; flex-direction: column; gap: 4px; }
        .msg-name { font-size: 12px; color: #7e57c2; font-weight: bold; margin-left: 2px; }
        .msg-row.me .msg-name { text-align: right; margin-right: 2px; }

        .msg-bubble {
            padding: 10px 16px; border-radius: 18px; font-size: 15px; line-height: 1.5;
            background: white; color: #455a64; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            border-top-left-radius: 4px; /* 말풍선 꼬리 효과 */
        }
        .msg-row.me .msg-bubble {
            background: var(--me); color: #5e35b1;
            border-radius: 18px; border-top-right-radius: 4px;
        }
        .msg-row.system .msg-bubble {
            background: rgba(0,0,0,0.05); color: #555; font-size: 13px;
            border-radius: 20px; padding: 6px 15px; text-align: center;
        }
        .msg-row.enemy .msg-bubble {
            background: #ffebee; color: #c62828; border: 1px solid #ffcdd2;
        }

        /* 하단 입력바 & 메뉴 */
        .input-dock {
            background: white; padding: 10px 15px; 
            border-top: 1px solid #f0f0f0; display: flex; gap: 8px; align-items: center;
        }
        .chat-input {
            flex: 1; padding: 12px; border: 1px solid #e1bee7; 
            border-radius: 20px; background: #fafafa; font-size: 15px;
        }
        .btn-send {
            width: 45px; height: 45px; border-radius: 50%; background: var(--pri); 
            color: white; border: none; font-size: 18px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(126, 87, 194, 0.3);
        }

        /* 액션 메뉴 (하단 오버레이) */
        #action-sheet {
            display: none; background: white; padding: 20px; 
            border-radius: 24px 24px 0 0; box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        .action-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .act-card {
            background: #f8f9fa; border: 1px solid #eee; border-radius: 16px;
            padding: 15px 0; text-align: center; cursor: pointer; transition: 0.2s;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            color: #546e7a; font-weight: bold; font-size: 13px;
        }
        .act-card:active { transform: scale(0.95); background: #eceff1; }
        .act-card.atk { color: #e57373; background: #ffebee; border-color: #ffcdd2; }
        .act-card.def { color: #64b5f6; background: #e3f2fd; border-color: #bbdefb; }
        .act-card i { font-size: 24px; margin-bottom: 4px; }
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
            <div id="battle-wait" class="wait-room" style="display:none; height:100%; flex-direction:column; align-items:center; justify-content:center;">
                <h2 style="margin-bottom:30px; color:var(--primary);"><i class="fa-solid fa-clock"></i> 대기실</h2>
                
                <div class="vs-badge" style="font-size:24px; margin-bottom:20px;">
                    <span id="wait-p1">???</span> VS <span id="wait-p2">???</span>
                </div>
                
                <p id="wait-status" style="color:#777; margin-bottom:40px; font-size:18px;">상대를 기다리는 중...</p>
                
                <div style="display:flex; gap:10px;">
                    <button id="btn-ready" class="ready-btn" onclick="App.toggleReady()">준비</button>
                    <button class="ready-btn" style="background:#e74c3c;" onclick="App.api({cmd:'battle_exit'}); App.showView('lobby');">나가기</button>
                </div>
            </div>

            <div id="battle-play" style="display:none; flex-direction:column; height:100%;">
                <div class="battle-header">
                    <div class="battle-title"><i class="fa-solid fa-comments"></i> BATTLE <span id="bt-room-id" style="font-size:12px; color:#aaa;">#12</span></div>
                    <div>
                        <span id="enemy-hp-pill" style="background:#e57373; color:white; padding:4px 8px; border-radius:12px; font-size:11px; font-weight:bold;">적 HP ?/?</span>
                        <button onclick="App.act('run')" style="background:none; border:none; color:#999; font-size:18px; margin-left:10px;"><i class="fa-solid fa-person-running"></i></button>
                    </div>
                </div>

                <div id="bt-chat-box" class="chat-area">
                    </div>

                <div id="action-sheet">
                    <div id="menu-attack" class="action-grid" style="display:none;">
                        <div class="act-card atk" onclick="App.act('attack')">
                            <i class="fa-solid fa-khanda"></i> 기본 공격
                        </div>
                        <div class="act-card" onclick="alert('스킬 준비중')">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> 스킬
                        </div>
                        <div class="act-card" onclick="location.href='inventory.php'">
                            <i class="fa-solid fa-bag-shopping"></i> 가방
                        </div>
                    </div>
                    <div id="menu-defend" class="action-grid" style="display:none;">
                        <div style="grid-column:1/-1; text-align:center; color:#e57373; margin-bottom:5px; font-weight:bold;">
                            ⚠️ 공격이 들어옵니다!
                        </div>
                        <div class="act-card def" onclick="App.defend('counter')">
                            <i class="fa-solid fa-gavel"></i> 반격 (주사위)
                        </div>
                        <div class="act-card def" onclick="App.defend('dodge')">
                            <i class="fa-solid fa-wind"></i> 회피 (민첩)
                        </div>
                        <div class="act-card" onclick="App.defend('hit')">
                            <i class="fa-solid fa-shield-halved"></i> 방어 (뎀감)
                        </div>
                    </div>
                </div>

                <div class="input-dock">
                    <button onclick="App.toggleActionMenu()" style="border:none; background:none; font-size:20px; color:#9575cd;">
                        <i class="fa-solid fa-circle-plus"></i>
                    </button>
                    <input type="text" id="bt-chat-input" class="chat-input" placeholder="대화하기..." onkeyup="if(event.key==='Enter') App.sendBattleChat()">
                    <button class="btn-send" onclick="App.sendBattleChat()"><i class="fa-solid fa-paper-plane"></i></button>
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
        const res = await this.api({ cmd: 'get_my_info' });
        
        if (res.status === 'error' || !res.data) {
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
            // [추가] 거절당했을 경우 처리
            if (res.active_room.status === 'REJECTED') {
                alert("상대방이 결투를 거절했습니다.");
                await this.api({ cmd: 'battle_exit' }); // 확인 누르면 방 완전히 나가기(END 처리)
                this.showView('lobby');
                return;
            }

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
        // [수정] 버튼 누르자마자 일단 창부터 닫기 (시야 확보)
        document.getElementById('alert-overlay').style.display = 'none';

        if (this.challengeId) {
            const res = await this.api({ cmd: 'battle_join', room_id: this.challengeId });
            if(res.status === 'success') {
                this.challengeId = 0;
                await this.poll(); // 대기실로 이동
            } else {
                alert(res.message);
            }
        }
    },

    async rejectChallenge() {
        if (this.challengeId) {
            // 서버에 거절 요청을 보냄
            await this.api({ cmd: 'battle_reject', room_id: this.challengeId });
        }
        document.getElementById('alert-overlay').style.display = 'none';
        this.challengeId = 0;
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

toggleActionMenu: function() {
        const sheet = document.getElementById('action-sheet');
        sheet.style.display = (sheet.style.display === 'block') ? 'none' : 'block';
    },

    refreshBattle: function() {
        if (!this.roomId) return;
        this.api({ cmd: 'battle_refresh' }).then(res => {
            if (res.status === 'end') {
                alert(res.win ? "승리!" : "전투 종료");
                this.roomId = 0;
                this.showView('lobby');
                return;
            }
            if (res.status !== 'battle') return;

            // 1. 몬스터 정보 (헤더에 작게 표시)
            const mob = res.enemies[0];
            if (mob) {
                document.getElementById('enemy-hp-pill').innerText = `${mob.name}: ${mob.hp_cur}/${mob.hp_max}`;
            }

            // 2. 턴 상태에 따른 메뉴 제어
            const sheet = document.getElementById('action-sheet');
            const menuAtk = document.getElementById('menu-attack');
            const menuDef = document.getElementById('menu-defend');
            const turn = res.room.turn_status;

            // 메뉴 기본 상태
            menuAtk.style.display = 'none';
            menuDef.style.display = 'none';

            if (turn === 'player') {
                sheet.style.display = 'block'; // 내 턴이면 메뉴 켬
                menuAtk.style.display = 'grid';
            } 
            else if (turn === 'defend_' + this.myId) {
                sheet.style.display = 'block'; // 방어 턴이면 메뉴 켬
                menuDef.style.display = 'grid';
            } 
            else {
                // 남의 턴이면 메뉴 닫기 (채팅에 집중)
                // 단, 사용자가 수동으로 열었을 수도 있으니 강제로 닫진 않음
            }

            // 3. 채팅/로그 렌더링 (Mafia Style)
            const box = document.getElementById('bt-chat-box');
            let html = '';
            
            res.logs.forEach(l => {
                let typeClass = 'other';
                let profileImg = l.profile ? l.profile : 'assets/images/user.png'; // 기본 이미지
                let name = l.name || 'System';

                if (l.uid == this.myId) {
                    typeClass = 'me';
                    profileImg = res.my_img || profileImg; // 내 프로필
                }
                
                if (l.type === 'system' || l.type === 'enemy_atk') {
                    typeClass = 'system';
                    if(l.type === 'enemy_atk') typeClass += ' enemy'; // 적 공격은 빨간색
                }

                if (typeClass === 'system') {
                    html += `
                        <div class="msg-row ${typeClass}">
                            <div class="msg-bubble">${l.msg}</div>
                        </div>`;
                } else {
                    // 일반 채팅 (프로필 + 이름 + 말풍선)
                    html += `
                        <div class="msg-row ${typeClass}">
                            <div class="msg-profile"><img src="${profileImg}"></div>
                            <div class="msg-content">
                                <div class="msg-name">${name}</div>
                                <div class="msg-bubble">${l.msg}</div>
                            </div>
                        </div>`;
                }
            });

            if (box.innerHTML !== html) {
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight;
            }
        });
    },

    async act(type) {
        if (type === 'run' && !confirm('도망치시겠습니까?')) return;
        const cmd = (type === 'run') ? 'battle_run' : 'battle_action_attack'; // battle_run은 api.php에 구현되어 있어야 함. 없으면 exit 사용
        
        // api.php에 battle_run이 없다면 battle_exit로 대체
        const finalCmd = (cmd === 'battle_run') ? 'battle_exit' : cmd; 
        
        await this.api({ cmd: finalCmd, room_id: this.roomId });
        this.refreshBattle();
    },

    // [index.php] App 객체 내부

    // 1. 방어 함수 수정 (room_id 누락 해결)
    async defend(type) {
        if (!this.roomId) return alert("방 번호 오류");
        
        // [수정] room_id를 반드시 같이 보내야 함!
        const res = await this.api({ 
            cmd: 'battle_action_defend', 
            room_id: this.roomId, 
            type: type 
        });
        
        if (res.status === 'success') {
            this.refreshBattle();
        } else {
            alert(res.message || "오류가 발생했습니다.");
        }
    },

    // 2. 채팅 전송 함수
    async sendBattleChat() {
        const input = document.getElementById('bt-chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        
        input.value = ''; // 내용 비우기
        input.style.height = '40px'; // 높이 초기화
        
        // 서버 전송
        await this.api({ cmd: 'battle_chat', room_id: this.roomId, msg: msg });
        this.refreshBattle();
    },

    // 3. 엔터키 핸들러 (신규)
    handleChatKey(e) {
        if (e.key === 'Enter') {
            if (!e.shiftKey) {
                // Shift 없이 엔터만 누르면 전송
                e.preventDefault();
                this.sendBattleChat();
            }
            // Shift+Enter는 기본 동작(줄바꿈) 허용
        }
    },
};

window.onload = () => App.init();
        </script>
    </body>
</html>