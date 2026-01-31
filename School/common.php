<?php
// common.php : 공통 함수, 설정, 시간 동기화 (기절/상태이상 페널티 포함 최종)
date_default_timezone_set('Asia/Seoul');

// 보안 헤더
header("Content-Security-Policy: default-src * 'self' 'unsafe-inline' 'unsafe-eval' data: gap: content:; style-src * 'self' 'unsafe-inline'; media-src *; img-src * data:;");

// 세션 설정
$lifetime = 86400;
ini_set('session.gc_maxlifetime', $lifetime);
ini_set('session.cookie_lifetime', $lifetime);
session_set_cookie_params(['lifetime' => $lifetime, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);

if (session_status() == PHP_SESSION_NONE) session_start();

// DB 설정 로드
if (!file_exists('config.php')) {
    if (basename($_SERVER['PHP_SELF']) != 'api.php') {
        echo "<script>location.href='setup.php';</script>"; exit;
    } else {
        echo json_encode(['status'=>'error', 'message'=>'시스템 미설치']); exit;
    }
}
require_once 'config.php';

$pdo = get_conn();
$pdo->exec("SET time_zone = '+09:00'");

try {
    $pdo->exec("ALTER TABLE School_Members ADD COLUMN injury INT DEFAULT 0");
} catch(Exception $e) {}
try {
    $pdo->exec("ALTER TABLE School_Battles ADD COLUMN target_id INT DEFAULT 0");
} catch(Exception $e) {}


// ---------------------------------------------------------
// [핵심] 생존 여부 체크 (사망자는 모든 행동 불가)
// ---------------------------------------------------------
function check_alive($uid) {
    $me = sql_fetch("SELECT injury FROM School_Members WHERE id=?", [$uid]);
    if ($me['injury'] >= 4) {
        throw new Exception("☠️ 사망하였습니다. 더 이상 행동할 수 없습니다.");
    }
}

// ---------------------------------------------------------
// [핵심] 기절 및 부상 처리 로직
// ---------------------------------------------------------
function check_faint($uid) {
    global $pdo;
    $me = sql_fetch("SELECT hp_current, hp_max, point, injury FROM School_Members WHERE id=?", [$uid]);
    
    // HP가 0 이하일 때
    if ($me['hp_current'] <= 0) {
        // 이미 사망 상태면 패스
        if ($me['injury'] >= 4) return;

        $new_injury = $me['injury'] + 1;
        
        if ($new_injury >= 4) {
            // [사망] 부상 4누적 -> 사망 처리
            sql_exec("UPDATE School_Members SET injury = ?, hp_current = 0 WHERE id = ?", [$new_injury, $uid]);
            $msg = "💀 부상이 악화되어 사망하였습니다... (부상 4/4)\\n이제 아무것도 할 수 없습니다.";
        } else {
            // [부상] 부상 1증가, HP 소량 회복(겨우 숨만 붙음), 포인트 차감
            $lost_point = floor($me['point'] * 0.2); // 20% 소실
            $recover_hp = floor($me['hp_max'] * 0.1); // 최대 체력 10%만 회복
            
            sql_exec("UPDATE School_Members SET 
                injury = ?, 
                hp_current = ?, 
                point = GREATEST(0, point - ?) 
                WHERE id = ?", 
                [$new_injury, $recover_hp, $lost_point, $uid]
            );
            $msg = "😵 치명적인 피해를 입었습니다! (부상 {$new_injury}/4)\\n응급처치로 겨우 깨어났습니다.\\n(치료비 -{$lost_point} P)";
        }
        
        $_SESSION['faint_msg'] = $msg;
        write_log($uid, 'BATTLE', $msg);
    }
}


// ---------------------------------------------------------
// [핵심 로직] 상태이상 진화 및 페널티 적용
// ---------------------------------------------------------
function check_status_evolution() {
    global $pdo;
    
    // 진화 가능한 상태이상 조회
    $list = sql_fetch_all("
        SELECT act.*, info.max_stage, info.stage_config, info.name 
        FROM School_Status_Active act
        JOIN School_Status_Info info ON act.status_id = info.status_id
    ");

    $now = time();
    
    foreach ($list as $row) {
        $cur = $row['current_stage'];
        $max = $row['max_stage'];
        
        if ($cur >= $max) continue; // 이미 최대 단계

        $cfg = json_decode($row['stage_config'], true);
        
        // 현재 단계의 지속시간 체크
        $req_time = isset($cfg[$cur]['time']) ? intval($cfg[$cur]['time']) : 300;
        $elapsed = $now - strtotime($row['last_evolved_at']);

        if ($elapsed >= $req_time) {
            $next = $cur + 1;
            
            // [추가] 진화 시 HP/포인트 페널티
            $cut_hp = isset($cfg[$next]['cut_hp']) ? intval($cfg[$next]['cut_hp']) : 0;
            $cut_point = isset($cfg[$next]['cut_point']) ? intval($cfg[$next]['cut_point']) : 0;
            
            // 유저 정보 업데이트
            if ($cut_hp > 0 || $cut_point > 0) {
                sql_exec("UPDATE School_Members SET 
                    hp_current = GREATEST(0, hp_current - ?), 
                    point = GREATEST(0, point - ?) 
                    WHERE id = ?", 
                    [$cut_hp, $cut_point, $row['target_id']]
                );
            }

            // 단계 업데이트
            sql_exec("UPDATE School_Status_Active SET current_stage = ?, last_evolved_at = NOW() WHERE id = ?", [$next, $row['id']]);
            
            // 로그 기록
            $penalty_txt = "";
            if($cut_hp > 0) $penalty_txt .= " (체력 -{$cut_hp})";
            if($cut_point > 0) $penalty_txt .= " (치료비 -{$cut_point}P)";

            write_log($row['target_id'], 'STATUS', "상태이상 [{$row['name']}]가 {$next}단계로 악화되었습니다.{$penalty_txt}");

            // [추가] 기절 체크
            check_faint($row['target_id']);
        }
    }
}


// ---------------------------------------------------------
// [헬퍼 함수]
// ---------------------------------------------------------
function write_log($uid, $type, $msg) {
    sql_exec("INSERT INTO School_Log (user_id, type, message, created_at) VALUES (?, ?, ?, NOW())", [$uid, $type, $msg]);
}

function json_res($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sql_exec($sql, $params = []) {
    global $pdo;
    try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return true; } 
    catch (Exception $e) { return false; }
}

function sql_fetch($sql, $params = []) {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sql_fetch_all($sql, $params = []) {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sql_one($sql, $params = []) {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn();
}

function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
function to_int($val) { return intval($val); }
?>