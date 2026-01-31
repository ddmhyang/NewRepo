<?php
// [1. 최상단 설정] 에러 메시지가 화면에 출력되는 것을 강제로 막습니다.
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start(); // 출력 버퍼링 (이상한 텍스트 출력 방지)

header('Content-Type: application/json; charset=utf-8');
require_once 'common.php';

// [2. JSON 출력 함수] 기존 출력을 지우고 깨끗한 JSON만 내보냅니다.
if (!function_exists('json_out')) {
    function json_out($data) {
        if (ob_get_length()) ob_clean(); // 기존에 쌓인 에러/공백 제거
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$cmd = isset($_POST['cmd']) ? $_POST['cmd'] : (isset($input['cmd']) ? $input['cmd'] : '');

// 로그인 체크 (로그인 요청 제외)
if ($cmd !== 'login' && !isset($_SESSION['uid'])) {
    json_res(['status'=>'error', 'message'=>'로그인이 필요합니다.']);
}

// 로그인 외 기능 수행 시 생존 여부 및 상태이상 체크
if (isset($_SESSION['uid'])) {
    check_status_evolution(); // 상태이상 시간 경과 체크 (common.php에 정의됨)
    
    // 사망해도 사용 가능한 안전한 명령어들
    $safe_cmds = ['login', 'get_my_info', 'battle_list_users', 'check_incoming_challenge', 'battle_chat_send', 'battle_refresh']; 
    
    // 그 외 명령어는 사망 시 차단
    if (!in_array($cmd, $safe_cmds)) check_alive($_SESSION['uid']);
}

try {
    $my_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : 0;
    
    switch ($cmd) {
        // =========================================================
        // [1] 유저 기본 (로그인/정보/프로필)
        // =========================================================
case 'login':
            $name = trim($input['name']);
            $pw = trim($input['pw']);
            if (!$name || !$pw) throw new Exception("정보를 입력하세요.");
            if ($name === 'admin') $user = sql_fetch("SELECT * FROM School_Members WHERE user_id = 'admin'");
            else $user = sql_fetch("SELECT * FROM School_Members WHERE name = ? AND role != 'admin'", [$name]);
            if (!$user || !password_verify($pw, $user['pw'])) throw new Exception("정보가 일치하지 않습니다.");
            $_SESSION['uid'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            sql_exec("UPDATE School_Members SET last_action_at = NOW() WHERE id = ?", [$user['id']]);
            json_res(['status'=>'success']);
            break;

        case 'logout':
            session_destroy();
            json_res(['status'=>'success']);
            break;

// [수정] get_my_info: 활동 시간 갱신 추가
        case 'get_my_info':
            // 접속 시마다 활동 시간 갱신 (유저 목록에 뜨게 하기 위함)
            sql_exec("UPDATE School_Members SET last_action_at = NOW() WHERE id = ?", [$my_id]);
            
            $me = sql_fetch("SELECT * FROM School_Members WHERE id = ?", [$my_id]);
            
            // 결투 신청 확인 (guest_id가 0이거나 나로 설정된 대기방)
            $challenge = sql_fetch("
                SELECT b.room_id, m.name 
                FROM School_Battles b
                JOIN School_Members m ON b.host_id = m.id
                WHERE b.target_id = ? AND b.status = 'WAIT'
                LIMIT 1
            ", [$my_id]);
            
            $active_room = sql_fetch("SELECT room_id, status FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('WAIT','READY','FIGHTING')", [$my_id, $my_id]);

            json_res([
                'status'=>'success', 
                'data'=>$me,
                'challenge'=>$challenge,
                'active_room'=>$active_room
            ]);
            break;
            
case 'battle_list_users':
            try {
                // 접속 10분 이내 유저 표시 (injury 컬럼 포함)
                $list = sql_all("SELECT id, name, level, injury FROM School_Members WHERE id != ? AND last_action_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND role != 'admin'", [$my_id]);
            } catch (Exception $e) {
                // 컬럼 에러 나면 injury 빼고 조회 (안전 모드)
                $list = sql_all("SELECT id, name, level FROM School_Members WHERE id != ? AND last_action_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND role != 'admin'", [$my_id]);
                foreach ($list as &$u) $u['injury'] = 0;
            }
            json_out(['status'=>'success', 'list'=>$list]);
            break;


case 'battle_make_room':
            // 1. 이미 방이 있는지 확인
            $chk = sql_fetch("SELECT room_id FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('WAIT','READY','FIGHTING')", [$my_id, $my_id]);
            
            if ($chk) {
                json_out(['status'=>'success', 'room_id'=>$chk['room_id']]);
            }
            
            // 2. 없으면 새로 생성 (PVE)
            sql_exec("INSERT INTO School_Battles (host_id, target_id, status, host_ready, guest_ready, created_at, updated_at) VALUES (?, 0, 'WAIT', 0, 0, NOW(), NOW())", [$my_id]);
            json_out(['status'=>'success', 'room_id'=>$pdo->lastInsertId()]);
            break;
        case 'battle_challenge':
            $target_id = to_int($input['target_id']);
            $chk = sql_fetch("SELECT room_id FROM School_Battles WHERE host_id=? OR guest_id=?", [$my_id, $my_id]);
            if ($chk) throw new Exception("이미 전투 중이거나 대기 중입니다.");

            sql_exec("INSERT INTO School_Battles (host_id, target_id, status, host_ready, guest_ready, created_at, updated_at) VALUES (?, ?, 'WAIT', 0, 0, NOW(), NOW())", [$my_id, $target_id]);
            json_res(['status'=>'success', 'msg'=>'결투장을 보냈습니다.']);
            break;

        case 'battle_join':
            $rid = to_int($input['room_id']);
            $room = sql_fetch("SELECT * FROM School_Battles WHERE room_id=? AND status='WAIT'", [$rid]);
            if (!$room) throw new Exception("입장할 수 없습니다.");
            
            sql_exec("UPDATE School_Battles SET guest_id=?, updated_at=NOW() WHERE room_id=?", [$my_id, $rid]);
            json_res(['status'=>'success', 'room_id'=>$rid]);
            break;

        // --- 대기실 로직 (레디) ---
        case 'battle_room_info':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('WAIT','READY','FIGHTING') ORDER BY room_id DESC LIMIT 1", [$my_id, $my_id]);
            if (!$room) { json_res(['status'=>'none']); break; }

            $host = sql_fetch("SELECT name FROM School_Members WHERE id=?", [$room['host_id']]);
            $guest = ($room['guest_id']) ? sql_fetch("SELECT name FROM School_Members WHERE id=?", [$room['guest_id']]) : null;

            json_res([
                'status'=>'success',
                'room'=>$room,
                'host_name'=>$host['name'],
                'guest_name'=>$guest ? $guest['name'] : '없음'
            ]);
            break;

        case 'battle_ready':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('WAIT','READY')", [$my_id, $my_id]);
            if (!$room) throw new Exception("대기방이 없습니다.");

            $is_host = ($room['host_id'] == $my_id);
            $field = $is_host ? 'host_ready' : 'guest_ready';
            $new_val = (!empty($input['ready'])) ? 1 : 0;

            // 준비 상태 저장
            sql_exec("UPDATE School_Battles SET {$field}=? WHERE room_id=?", [$new_val, $room['room_id']]);
            
            // 다시 조회해서 시작 가능한지 확인
            $check = sql_fetch("SELECT * FROM School_Battles WHERE room_id=?", [$room['room_id']]);
            $can_start = false;
            
            // PVE(탐색): 호스트 준비시 시작 / PVP(결투): 둘 다 준비시 시작
            if ($check['target_id'] == 0 && $check['host_ready'] == 1) $can_start = true;
            elseif ($check['target_id'] > 0 && $check['host_ready'] == 1 && $check['guest_ready'] == 1) $can_start = true;

            if ($can_start) {
                start_battle($check['room_id'], $my_id, $input); // 전투 시작 함수 호출
                return; 
            }

            json_out(['status'=>'success']);
            break;

        // --- 전투 시작 (내부 호출용 label) ---
        case 'battle_start':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('WAIT','READY')", [$my_id, $my_id]);
            if (!$room) throw new Exception("시작할 방이 없습니다.");

            start_battle($room['room_id'], $my_id, $input);
            // 몬스터 / 플레이어 세팅
            $players_list = [$room['host_id']];
            if ($room['guest_id']) $players_list[] = $room['guest_id'];

            $mob_live_data = [];
            $logs = [];

            // PVP / PVE 분기
            if ($room['target_id'] > 0) {
                $logs[] = ['msg' => "⚔️ 결투가 시작되었습니다!", 'type' => 'system'];
            } else {
                // 몬스터 개수 (기본 1~3, 인원 많으면 추가)
                $mob_count = isset($input['mob_count']) ? max(1, to_int($input['mob_count'])) : rand(1, 3);
                $base_mob = sql_fetch("SELECT * FROM School_Monsters ORDER BY RAND() LIMIT 1");
                
                for($i=0; $i<$mob_count; $i++) {
                    $m_st = json_decode($base_mob['stats'], true);
                    $m_calc = calc_battle_stats($m_st);
                    if ($mob_count > 1) $m_calc['atk'] = floor($m_calc['atk'] * (1 - ($mob_count * 0.05))); // 너프

                    $mob_live_data[] = [
                        'id' => 'mob_'.$i, 'name' => $base_mob['name']." ".($i+1),
                        'hp_max' => $m_calc['hp_max'], 'hp_cur' => $m_calc['hp_max'],
                        'atk' => $m_calc['atk'], 'def' => $m_calc['def'], 'speed' => $m_calc['speed'],
                        'is_dead' => false
                    ];
                }
                $logs[] = ['msg' => "<b>{$base_mob['name']}</b> {$mob_count}마리가 나타났다!", 'type' => 'system'];
            }

            // 플레이어 스탯 계산
            $players_data = [];
            $max_speed_player = 0;
            foreach($players_list as $pid) {
                $p_db = sql_fetch("SELECT * FROM School_Members WHERE id=?", [$pid]);
                // 장비/상태이상 스탯 반영 생략(기존 코드 참고하여 구현)
                $p_calc = calc_battle_stats($p_db); 
                $p_calc['id'] = $pid;
                $p_calc['name'] = $p_db['name'];
                $p_calc['hp_cur'] = $p_db['hp_current'];
                $p_calc['is_dead'] = false;
                if ($p_calc['speed'] > $max_speed_player) $max_speed_player = $p_calc['speed'];
                $players_data[] = $p_calc;
            }

            $turn = ($max_speed_player >= ($mob_live_data[0]['speed'] ?? 0)) ? 'player' : 'enemy_ready';

            sql_exec("UPDATE School_Battles SET status='FIGHTING', mob_live_data=?, players_data=?, battle_log=?, turn_status=? WHERE room_id=?", 
                [json_encode($mob_live_data), json_encode($players_data), json_encode($logs), $turn, $room['room_id']]
            );
            json_res(['status'=>'success', 'start'=>true]);
            break;

        // --- 전투 진행 ---
        case 'battle_refresh': // (구 battle_info)
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status IN ('FIGHTING','END')", [$my_id, $my_id]);
            if (!$room) { json_res(['status'=>'none']); break; }

            // 전투 종료 상태면 클라이언트에 알림
            if ($room['status'] === 'END') {
                // 방 삭제 혹은 유지 정책 (여기선 DB에서 삭제하지 않고 클라가 나가면 처리하도록 둠)
                json_res(['status'=>'end', 'win'=>true]); // 승패 여부는 로그 등으로 판단
                break;
            }

            $mobs = json_decode($room['mob_live_data'], true);
            
            // [중요] 몬스터 전멸 체크 (버그 수정)
            $alive_mobs = 0;
            foreach($mobs as $m) if(!$m['is_dead']) $alive_mobs++;
            
            if ($alive_mobs === 0 && $room['target_id'] == 0) {
                // 즉시 승리 처리
                sql_exec("UPDATE School_Battles SET status='END' WHERE room_id=?", [$room['room_id']]);
                json_res(['status'=>'end', 'win'=>true]);
                break;
            }

            json_res([
                'status' => 'battle',
                'room' => $room,
                'me_id' => $my_id,
                'players' => json_decode($room['players_data'], true),
                'enemies' => $mobs,
                'logs' => json_decode($room['battle_log'], true)
            ]);
            break;

        case 'battle_action_attack':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE room_id=?", [$input['room_id']]);
            if (!$room || $room['status'] !== 'FIGHTING') throw new Exception("전투 중이 아닙니다.");
            
            $mobs = json_decode($room['mob_live_data'], true);
            $players = json_decode($room['players_data'], true);
            $logs = json_decode($room['battle_log'], true);

            // 타겟 선정 (살아있는 몹)
            $target_idx = -1;
            foreach($mobs as $idx => $m) { if(!$m['is_dead']) { $target_idx = $idx; break; } }
            
            if ($target_idx == -1) {
                // 이미 다 죽음 -> 승리
                sql_exec("UPDATE School_Battles SET status='END' WHERE room_id=?", [$room['room_id']]);
                json_res(['status'=>'win']);
            }

            $me = null; foreach($players as $p) if($p['id'] == $my_id) $me = $p;
            $dmg = max(1, $me['atk'] - $mobs[$target_idx]['def']);
            
            $mobs[$target_idx]['hp_cur'] -= $dmg;
            $logs[] = ['msg'=>"⚔️ <b>{$mobs[$target_idx]['name']}</b>에게 {$dmg} 피해!", 'type'=>'player'];

            if ($mobs[$target_idx]['hp_cur'] <= 0) {
                $mobs[$target_idx]['hp_cur'] = 0;
                $mobs[$target_idx]['is_dead'] = true;
                $logs[] = ['msg'=>"💀 {$mobs[$target_idx]['name']} 처치!", 'type'=>'system'];
            }

            // 전멸 확인
            $all_dead = true;
            foreach($mobs as $m) if(!$m['is_dead']) $all_dead = false;

            if ($all_dead) {
                $logs[] = ['msg'=>"🏆 <b>전투 승리!</b>", 'type'=>'system'];
                sql_exec("UPDATE School_Battles SET status='END', mob_live_data=?, battle_log=? WHERE room_id=?", 
                    [json_encode($mobs), json_encode($logs), $room['room_id']]);
                
                // 보상 지급 로직 추가 가능
                json_res(['status'=>'win']);
            } else {
                // 턴 넘기기
                sql_exec("UPDATE School_Battles SET mob_live_data=?, battle_log=?, turn_status='enemy_ready' WHERE room_id=?", 
                    [json_encode($mobs), json_encode($logs), $room['room_id']]);
                json_res(['status'=>'success']);
            }
            break;
            
        case 'battle_exit':
            // 방 나가기 (상태 END로 만듦)
            sql_exec("UPDATE School_Battles SET status='END' WHERE host_id=? OR guest_id=?", [$my_id, $my_id]);
            json_res(['status'=>'success']);
            break;


        case 'update_profile_img_file':
            if (!isset($_FILES['img_file']) || $_FILES['img_file']['error'] != UPLOAD_ERR_OK) {
                throw new Exception("파일 업로드 실패");
            }
            
            $file = $_FILES['img_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) throw new Exception("이미지 파일만 가능합니다.");
            
            if(!is_dir('uploads')) mkdir('uploads', 0777, true);
            $filename = "profile_{$my_id}_" . time() . "." . $ext; 
            $dest = "uploads/" . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                sql_exec("UPDATE School_Members SET img_profile=? WHERE id=?", [$dest, $my_id]);
                json_res(['status'=>'success']);
            } else {
                throw new Exception("파일 저장 실패");
            }
            break;

        case 'update_profile':
            $img = trim($input['image']);
            if (!$img) throw new Exception("이미지 주소를 입력하세요.");
            sql_exec("UPDATE School_Members SET image_url=? WHERE id=?", [$img, $my_id]);
            write_log($my_id, 'SYSTEM', '프로필 이미지를 변경했습니다.');
            json_res(['status'=>'success', 'msg'=>'변경 완료']);
            break;

        // =========================================================
        // [2] 아이템 사용 및 양도
        // =========================================================
        case 'use_item':
            $inv_id = to_int($input['inv_id']);
            
            $inv = sql_fetch("
                SELECT inv.*, i.type, i.effect_data, i.max_dur, i.name 
                FROM School_Inventory inv 
                JOIN School_Item_Info i ON inv.item_id = i.item_id 
                WHERE inv.id=? AND inv.owner_id=?", 
                [$inv_id, $my_id]
            );
            
            if (!$inv) throw new Exception("아이템이 없습니다.");
            if ($inv['type'] !== 'CONSUME' && $inv['type'] !== 'consumable') throw new Exception("장비는 사용할 수 없습니다. 장착하세요.");
            
            $eff = json_decode($inv['effect_data'], true);
            $msg = "[{$inv['name']}] 사용:";
            $me = sql_fetch("SELECT * FROM School_Members WHERE id=?", [$my_id]);

            // 1. HP 회복
            if (!empty($eff['hp_heal'])) {
                $heal = intval($eff['hp_heal']);
                $new_hp = min($me['hp_max'], $me['hp_current'] + $heal);
                sql_exec("UPDATE School_Members SET hp_current=? WHERE id=?", [$new_hp, $my_id]);
                $msg .= " 체력 {$heal} 회복.";
            }

            // 2. 상태이상 관리 (부여/치료/악화/완화)
            if (!empty($eff['status_id']) && !empty($eff['status_act'])) {
                $sid = intval($eff['status_id']);
                $act = $eff['status_act'];
                
                $st_info = sql_fetch("SELECT name FROM School_Status_Info WHERE status_id=?", [$sid]);
                $st_name = $st_info['name'] ?? '알 수 없는 병';

                if ($act === 'add') {
                    sql_exec("INSERT IGNORE INTO School_Status_Active (target_id, status_id, current_stage, created_at, last_evolved_at) VALUES (?, ?, 1, NOW(), NOW())", [$my_id, $sid]);
                    $msg .= " [{$st_name}]에 감염되었습니다.";
                }
                elseif ($act === 'cure') {
                    sql_exec("DELETE FROM School_Status_Active WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                    $msg .= " [{$st_name}] 치료됨.";
                }
                elseif ($act === 'up') {
                    $chk = sql_fetch("SELECT id FROM School_Status_Active WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                    if($chk) {
                        sql_exec("UPDATE School_Status_Active SET current_stage = current_stage + 1 WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                        $msg .= " [{$st_name}] 악화됨.";
                    }
                }
                elseif ($act === 'down') {
                    $cur = sql_fetch("SELECT current_stage FROM School_Status_Active WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                    if($cur) {
                        if($cur['current_stage'] > 1) {
                            sql_exec("UPDATE School_Status_Active SET current_stage = current_stage - 1 WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                            $msg .= " [{$st_name}] 호전됨.";
                        } else {
                            sql_exec("DELETE FROM School_Status_Active WHERE target_id=? AND status_id=?", [$my_id, $sid]);
                            $msg .= " [{$st_name}] 완치됨.";
                        }
                    }
                }
            }

            // 아이템 차감
            if ($inv['count'] > 1) {
                sql_exec("UPDATE School_Inventory SET count = count - 1 WHERE id=?", [$inv_id]);
            } else {
                sql_exec("DELETE FROM School_Inventory WHERE id=?", [$inv_id]);
            }
            
            write_log($my_id, 'ITEM', $msg);
            json_res(['status'=>'success', 'msg'=>$msg]);
            break;

        case 'transfer':
            $target_id = to_int($input['target_id']);
            $type = $input['type']; 
            
            if ($target_id == $my_id) throw new Exception("자신에게 보낼 수 없습니다.");
            $target = sql_fetch("SELECT id, name FROM School_Members WHERE id=?", [$target_id]);
            if (!$target) throw new Exception("존재하지 않는 대상입니다.");

            $pdo->beginTransaction();
            try {
                if ($type === 'point') {
                    $amount = to_int($input['amount']);
                    if ($amount <= 0) throw new Exception("올바른 금액을 입력하세요.");
                    
                    $me = sql_fetch("SELECT point FROM School_Members WHERE id=? FOR UPDATE", [$my_id]);
                    if ($me['point'] < $amount) throw new Exception("포인트가 부족합니다.");
                    
                    sql_exec("UPDATE School_Members SET point = point - ? WHERE id=?", [$amount, $my_id]);
                    sql_exec("UPDATE School_Members SET point = point + ? WHERE id=?", [$amount, $target_id]);
                    $msg = "{$target['name']}님에게 {$amount} P를 보냈습니다.";
                    write_log($my_id, 'POINT', "{$target['name']}님에게 {$amount} P 양도");
                    write_log($target_id, 'POINT', "{$_SESSION['name']}님으로부터 {$amount} P 받음");
                } 
                elseif ($type === 'item') {
                    $inv_id = to_int($input['inv_id']);
                    $count = to_int($input['count']);
                    if ($count <= 0) throw new Exception("수량을 확인하세요.");

                    $my_inv = sql_fetch("SELECT inv.*, info.name FROM School_Inventory inv JOIN School_Item_Info info ON inv.item_id=info.item_id WHERE inv.id=? AND inv.owner_id=? FOR UPDATE", [$inv_id, $my_id]);
                    if (!$my_inv || $my_inv['count'] < $count) throw new Exception("아이템이 부족합니다.");
                    if ($my_inv['is_equipped']) throw new Exception("장착 중인 아이템은 보낼 수 없습니다.");

                    if ($my_inv['count'] == $count) sql_exec("DELETE FROM School_Inventory WHERE id=?", [$inv_id]);
                    else sql_exec("UPDATE School_Inventory SET count = count - ? WHERE id=?", [$count, $inv_id]);

                    sql_exec("INSERT INTO School_Inventory (owner_id, item_id, count, cur_dur) VALUES (?, ?, ?, ?)", 
                        [$target_id, $my_inv['item_id'], $count, $my_inv['cur_dur']]
                    );
                    $msg = "{$target['name']}님에게 {$my_inv['name']}을(를) 보냈습니다.";
                    write_log($my_id, 'ITEM', "{$target['name']}님에게 {$my_inv['name']} {$count}개 양도");
                    write_log($target_id, 'ITEM', "{$_SESSION['name']}님으로부터 {$my_inv['name']} {$count}개 받음");
                }
                $pdo->commit();
                json_res(['status'=>'success', 'msg'=>$msg]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // =========================================================
        // [3] 전투 시스템 (다수 몹 & 밸런스 패치 적용)
        // =========================================================
        
        case 'battle_chat_send':
            $msg = trim($input['msg']);
            if (!$msg) throw new Exception("");
            $room = sql_fetch("SELECT room_id FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status != 'END'", [$my_id, $my_id]);
            if (!$room) throw new Exception("전투 중이 아닙니다.");
            
            $me = sql_fetch("SELECT name FROM School_Members WHERE id=?", [$my_id]);
            sql_exec("INSERT INTO School_Battle_Chat (room_id, user_id, name, message, type) VALUES (?, ?, ?, ?, 'CHAT')", 
                [$room['room_id'], $my_id, $me['name'], $msg]);
            json_res(['status'=>'success']);
            break;



        case 'battle_info':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status='FIGHTING' ORDER BY room_id DESC LIMIT 1", [$my_id, $my_id]);
            if (!$room) json_res(['status'=>'ended']);

            $room['mob_live_data'] = json_decode($room['mob_live_data'], true);
            $room['players_data'] = json_decode($room['players_data'], true);
            $room['battle_log'] = json_decode($room['battle_log'], true);
            
            // 적 턴 시작 처리
            if ($room['turn_status'] === 'enemy_ready') {
                $alive_mobs = array_filter($room['mob_live_data'], function($m){ return !$m['is_dead']; });
                
                if (empty($alive_mobs)) {
                    // 몹 전멸 -> 플레이어 턴으로 넘겨서 승리 처리 유도
                    sql_exec("UPDATE School_Battles SET turn_status='player' WHERE room_id=?", [$room['room_id']]);
                } else {
                    $atk_roll = rand(1, 100);
                    $msg = "👹 <b>좀비들</b>이 공격해옵니다! (총 " . count($alive_mobs) . "마리)<br>어떻게 할까? [반격 / 회피 / 맞기]";
                    $room['battle_log'][] = ['msg'=>$msg, 'type'=>'enemy'];

                    sql_exec("UPDATE School_Battles SET turn_status=?, enemy_roll=?, battle_log=? WHERE room_id=?", 
                        ['player_defend', $atk_roll, json_encode($room['battle_log']), $room['room_id']]
                    );
                }
            }
            json_res(['status'=>'playing', 'data'=>$room]);
            break;


        // 플레이어 방어 (적 턴)
        case 'battle_action_defend':
            $type = $input['type'];
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status='FIGHTING' ORDER BY room_id DESC LIMIT 1", [$my_id, $my_id]);
            if (!$room || $room['turn_status'] !== 'player_defend') throw new Exception("타이밍이 아닙니다.");

            $mobs = json_decode($room['mob_live_data'], true);
            $players = json_decode($room['players_data'], true);
            $logs = json_decode($room['battle_log'], true);
            
            // 살아있는 몹들이 랜덤 플레이어 공격
            $alive_mobs = array_filter($mobs, function($m){ return !$m['is_dead']; });
            
            foreach($alive_mobs as $mob) {
                $alive_players_idx = [];
                foreach($players as $idx=>$p) if(!$p['is_dead']) $alive_players_idx[] = $idx;
                if (empty($alive_players_idx)) break; 
                
                $target_idx = $alive_players_idx[array_rand($alive_players_idx)];
                $me = &$players[$target_idx];

                $dmg_msg = "";
                $is_hit = false;

                // 방어 행동 판정
                if ($type === 'dodge') {
                    $chance = min(90, $me['dex'] * 3);
                    if (rand(1, 100) <= $chance) $dmg_msg = "💨 {$me['name']} 회피!";
                    else $is_hit = true;
                } elseif ($type === 'counter') {
                    if (rand(1,100) > $room['enemy_roll']) {
                        $c_dmg = max(1, $me['atk'] - $mob['def']);
                        $mob['hp_cur'] -= $c_dmg;
                        $dmg_msg = "✨ {$me['name']} 반격 성공! ({$c_dmg} 피해)";
                    } else {
                        $is_hit = true;
                        $dmg_msg = "💦 반격 실패..";
                    }
                } else {
                    $is_hit = true; // hit
                }

                // 피격 처리
                if ($is_hit) {
                    $base_dmg = max(1, $mob['atk'] - $me['def']);
                    if ($type === 'hit') $base_dmg = round($base_dmg * 0.7); // 방어 시 경감
                    
                    $me['hp_cur'] -= $base_dmg;
                    $dmg_msg .= " 💥 {$me['name']} 피격 (-{$base_dmg})";
                    
                    // 방어구 내구도 (모든 부위)
                    $armor = sql_fetch("SELECT inv.id, inv.cur_dur FROM School_Inventory inv JOIN School_Item_Info i ON inv.item_id=i.item_id WHERE inv.owner_id=? AND inv.is_equipped=1 AND i.type IN ('HAT','FACE','TOP','BOTTOM','GLOVES','SHOES','ARMOR') ORDER BY RAND() LIMIT 1", [$me['id']]);
                    if($armor && rand(1,5)==1) sql_exec("UPDATE School_Inventory SET cur_dur = cur_dur - 1 WHERE id=?", [$armor['id']]);
                }
                
                $logs[] = ['msg'=>"<b>{$mob['name']}</b>의 공격: " . $dmg_msg, 'type'=>'enemy'];
                
                // 사망 체크
                if ($me['hp_cur'] <= 0) {
                    $me['hp_cur'] = 0; $me['is_dead'] = true;
                    $logs[] = ['msg'=>"💀 <b>{$me['name']}</b>님이 쓰러졌습니다...", 'type'=>'system'];
                }
            }

            // 전멸 체크
            $all_dead = true;
            foreach($players as $p) if(!$p['is_dead']) $all_dead = false;

            if ($all_dead) {
                $logs[] = ['msg'=>"전멸했습니다... (패배)", 'type'=>'system'];
                
                // 패널티 및 상태이상 심화 적용
                foreach($players as $p) {
                    // 1. 포인트 감소
                    sql_exec("UPDATE School_Members SET hp_current=1, point=GREATEST(0, point-50) WHERE id=?", [$p['id']]);
                    
                    // 2. 상태이상 단계 상승 (패배 시)
                    // 현재 활성화된 상태이상이 있다면 1개 골라서 단계 상승
                    $active_status = sql_fetch("SELECT id, status_id, current_stage FROM School_Status_Active WHERE target_id=? ORDER BY RAND() LIMIT 1", [$p['id']]);
                    if ($active_status) {
                        $s_info = sql_fetch("SELECT max_stage, name FROM School_Status_Info WHERE status_id=?", [$active_status['status_id']]);
                        if ($active_status['current_stage'] < $s_info['max_stage']) {
                            sql_exec("UPDATE School_Status_Active SET current_stage = current_stage + 1 WHERE id=?", [$active_status['id']]);
                            write_log($p['id'], 'BATTLE', "패배로 인한 {$s_info['name']} 악화");
                        }
                    } else {
                        // 상태이상이 없다면 랜덤 감염 (선택사항)
                        $rnd_st = sql_fetch("SELECT status_id FROM School_Status_Info ORDER BY RAND() LIMIT 1");
                        if ($rnd_st) {
                            sql_exec("INSERT INTO School_Status_Active (target_id, status_id, current_stage) VALUES (?, ?, 1)", [$p['id'], $rnd_st['status_id']]);
                        }
                    }
                }

                sql_exec("UPDATE School_Battles SET status='ENDED', players_data=?, battle_log=? WHERE room_id=?", 
                    [json_encode($players), json_encode($logs), $room['room_id']]
                );
                json_res(['status'=>'lose']);
            } else {
                sql_exec("UPDATE School_Battles SET turn_status='player', mob_live_data=?, players_data=?, battle_log=? WHERE room_id=?", 
                    [json_encode($mobs), json_encode($players), json_encode($logs), $room['room_id']]
                );
                foreach($players as $p) {
                    sql_exec("UPDATE School_Members SET hp_current=? WHERE id=?", [$p['hp_cur'], $p['id']]);
                }
                json_res(['status'=>'success']);
            }
            break;

        case 'battle_run':
            $room = sql_fetch("SELECT * FROM School_Battles WHERE (host_id=? OR guest_id=?) AND status='FIGHTING'", [$my_id, $my_id]);
            if(rand(1,100) <= 50) {
                sql_exec("UPDATE School_Battles SET status='ENDED' WHERE room_id=?", [$room['room_id']]);
                json_res(['status'=>'success', 'msg'=>'도망쳤습니다!']);
            } else {
                json_res(['status'=>'fail', 'msg'=>'도망 실패!']);
            }
            break;

        // =========================================================
        // [4] 인벤토리 액션 (장비 슬롯 제한 등)
        // =========================================================
        case 'inventory_action':
            $inv_id = to_int($input['inv_id']);
            $action = $input['action']; 
            
            $item = sql_fetch("SELECT inv.*, info.type, info.name, info.effect_data 
                               FROM School_Inventory inv 
                               JOIN School_Item_Info info ON inv.item_id = info.item_id 
                               WHERE inv.id=? AND inv.owner_id=?", [$inv_id, $my_id]);
            
            if (!$item) throw new Exception("아이템이 존재하지 않습니다.");

            if ($action === 'equip') {
                $allowed_slots = ['WEAPON', 'HAT', 'FACE', 'TOP', 'BOTTOM', 'GLOVES', 'SHOES'];
                
                if ($item['type'] === 'ETC') {
                     $cnt = sql_fetch("SELECT count(*) as c FROM School_Inventory inv 
                                       JOIN School_Item_Info info ON inv.item_id = info.item_id 
                                       WHERE inv.owner_id=? AND inv.is_equipped=1 AND info.type='ETC'", [$my_id]);
                     if ($cnt['c'] >= 5) throw new Exception("장신구(기타)는 최대 5개까지만 장착 가능합니다.");
                } 
                elseif (in_array($item['type'], $allowed_slots)) {
                    // 같은 부위 자동 해제
                    sql_exec("UPDATE School_Inventory inv 
                              JOIN School_Item_Info info ON inv.item_id = info.item_id 
                              SET inv.is_equipped = 0 
                              WHERE inv.owner_id = ? AND info.type = ? AND inv.is_equipped = 1", 
                              [$my_id, $item['type']]);
                } 
                else {
                    throw new Exception("이 아이템은 장착할 수 없습니다.");
                }

                sql_exec("UPDATE School_Inventory SET is_equipped = 1 WHERE id=?", [$inv_id]);
                write_log($my_id, 'ITEM', "{$item['name']} 장착");
                json_res(['status'=>'success', 'msg'=>'장착 완료']);
            } 
            elseif ($action === 'unequip') {
                sql_exec("UPDATE School_Inventory SET is_equipped = 0 WHERE id=?", [$inv_id]);
                write_log($my_id, 'ITEM', "{$item['name']} 해제");
                json_res(['status'=>'success', 'msg'=>'해제 완료']);
            } 
            elseif ($action === 'use') {
                // (위의 use_item과 로직 공유하거나 여기서 호출)
                // 편의상 use_item case를 다시 호출하는 게 좋지만, 구조상 복붙
                if ($item['type'] !== 'CONSUME' && $item['type'] !== 'consumable') throw new Exception("사용할 수 없는 아이템입니다.");
                
                $eff = json_decode($item['effect_data'], true);
                $me = sql_fetch("SELECT hp_current, hp_max FROM School_Members WHERE id=?", [$my_id]);
                
                if (isset($eff['hp_heal'])) {
                    $new_hp = min($me['hp_max'], $me['hp_current'] + $eff['hp_heal']);
                    sql_exec("UPDATE School_Members SET hp_current=? WHERE id=?", [$new_hp, $my_id]);
                }
                
                if ($item['count'] > 1) sql_exec("UPDATE School_Inventory SET count = count - 1 WHERE id=?", [$inv_id]);
                else sql_exec("DELETE FROM School_Inventory WHERE id=?", [$inv_id]);
                
                json_res(['status'=>'success', 'msg'=>'아이템 사용 완료']);
            }
            break;

        // =========================================================
        // [5] 도박 (홀짝, 룰렛, 블랙잭)
        // =========================================================
case 'gamble_hj':
            $amount = to_int($input['amount']);
            $pick = $input['pick'];
            if ($amount <= 0) throw new Exception("배팅 금액 확인");
            $me = sql_fetch("SELECT point FROM School_Members WHERE id=?", [$my_id]);
            if ($me['point'] < $amount) throw new Exception("포인트 부족");
            
            // 배팅 차감
            sql_exec("UPDATE School_Members SET point = point - ? WHERE id=?", [$amount, $my_id]);
            
            $dice = rand(1, 10);
            $result = ($dice % 2 !== 0) ? 'odd' : 'even';
            $is_win = ($pick === $result);
            $current_point = $me['point'] - $amount;
            $gain = 0; // 순수 획득량

            if ($is_win) {
                // 승리 시 2배 지급 (원금+원금)
                $payout = floor($amount * 2);
                $gain = $payout; // 이미 배팅금 깠으므로 받는 돈이 전액 gain은 아님. 순이익은 amount.
                sql_exec("UPDATE School_Members SET point = point + ? WHERE id=?", [$payout, $my_id]);
                $current_point += $payout;
                json_res(['status'=>'win', 'result'=>$result, 'gain'=>$payout, 'current_point'=>$current_point]);
            } else {
                json_res(['status'=>'lose', 'result'=>$result, 'current_point'=>$current_point]);
            }
            break;
// [추가] 룰렛 종류 가져오기
        case 'get_roulette_types':
            $types = sql_fetch_all("SELECT DISTINCT game_type FROM School_Gamble_Config");
            $list = [];
            foreach($types as $t) $list[] = $t['game_type'];
            if(empty($list)) $list = ['기본']; // 없을 경우 기본값
            json_res(['status'=>'success', 'list'=>$list]);
            break;

        // [수정] 룰렛 돌리기
        case 'gamble_roulette':
            $bet = to_int($input['amount']);
            $type = isset($input['type']) ? $input['type'] : '기본'; // 게임 종류
            
            if ($bet <= 0) throw new Exception("배팅 금액을 확인하세요.");

            $me = sql_fetch("SELECT point FROM School_Members WHERE id=?", [$my_id]);
            if ($me['point'] < $bet) throw new Exception("포인트가 부족합니다.");

            // 1. 해당 타입의 설정 불러오기
            $configs = sql_fetch_all("SELECT * FROM School_Gamble_Config WHERE game_type=?", [$type]);
            if (!$configs) throw new Exception("설정된 룰렛 데이터가 없습니다.");

            // 2. 확률 기반 아이템 뽑기 (가중치 랜덤)
            $total_prob = 0;
            foreach($configs as $c) $total_prob += $c['probability'];
            
            $rand = rand(1, $total_prob);
            $current = 0;
            $selected = null;
            
            foreach($configs as $c) {
                $current += $c['probability'];
                if ($rand <= $current) {
                    $selected = $c;
                    break;
                }
            }
            if(!$selected) $selected = $configs[count($configs)-1]; // Fallback

            // 3. 결과 계산 (중요: 배팅금 선차감 로직)
            // 공식: (현재포인트 - 배팅금) + (배팅금 * 배율)
            // 배율이 2배면: -100 + 200 = +100 이득
            // 배율이 -1배면: -100 + (-100) = -200 손해
            
            $ratio = (float)$selected['ratio'];
            $payout = floor($bet * $ratio); // 배당금 (음수일 수도 있음)
            $net_change = $payout - $bet;   // 최종 변동액 (배당금 - 배팅비용)

            // 포인트 업데이트 (음수 허용을 위해 GREATEST 제거 가능)
            // 만약 포인트가 0 미만으로 떨어지는 걸 허용한다면 아래처럼:
            sql_exec("UPDATE School_Members SET point = point + ? WHERE id=?", [$net_change, $my_id]);
            
            // 로그
            write_log($my_id, 'GAMBLE', "룰렛[{$type}]: {$selected['name']} (x{$ratio}) / 변동: {$net_change} P");

            json_res([
                'status' => 'success',
                'data' => [
                    'name' => $selected['name'], 
                    'ratio' => $ratio
                ],
                'gain' => $net_change, // 클라이언트에 표시할 순이익/순손실
                'current_point' => $me['point'] + $net_change
            ]);
            break;

        case 'gamble_bj_start':
            $amount = to_int($input['amount']);
            if ($amount <= 0) throw new Exception("금액 오류");
            $me = sql_fetch("SELECT point FROM School_Members WHERE id=?", [$my_id]);
            if ($me['point'] < $amount) throw new Exception("포인트 부족");
            
            sql_exec("UPDATE School_Members SET point = point - ? WHERE id=?", [$amount, $my_id]);
            
            $p_hand = [rand(1, 13), rand(1, 13)];
            $d_hand = [rand(1, 13), rand(1, 13)];
            $_SESSION['bj_game'] = ['bet' => $amount, 'p_hand' => $p_hand, 'd_hand' => $d_hand, 'status' => 'playing'];
            
            json_res(['status'=>'success', 'data'=>['player_hand'=>$p_hand, 'dealer_hand'=>$d_hand, 'player_score'=>calc_bj_score($p_hand), 'dealer_score'=>calc_bj_score($d_hand)], 'current_point'=>$me['point']-$amount]);
            break;

        case 'gamble_bj_action':
            if (!isset($_SESSION['bj_game']) || $_SESSION['bj_game']['status'] !== 'playing') throw new Exception("게임 없음");
            $game = &$_SESSION['bj_game'];
            $action = $input['action'];
            $is_end = false; $msg = "";
            
            if ($action === 'hit') {
                $game['p_hand'][] = rand(1, 13);
                if (calc_bj_score($game['p_hand']) > 21) { $is_end = true; $msg = "버스트! 패배"; }
            } elseif ($action === 'stand') {
                while (calc_bj_score($game['d_hand']) < 17) { $game['d_hand'][] = rand(1, 13); }
                $is_end = true;
                $p_score = calc_bj_score($game['p_hand']);
                $d_score = calc_bj_score($game['d_hand']);
                $bet = $game['bet'];
                $win = 0;
                
                if ($d_score > 21 || $p_score > $d_score) { $msg = "승리!"; $win = $bet*2; }
                elseif ($p_score == $d_score) { $msg = "무승부"; $win = $bet; }
                else { $msg = "패배..."; }
                
                if ($win > 0) sql_exec("UPDATE School_Members SET point = point + ? WHERE id=?", [$win, $my_id]);
            }

            $me = sql_fetch("SELECT point FROM School_Members WHERE id=?", [$my_id]);
            $data = ['player_hand' => $game['p_hand'], 'dealer_hand' => $game['d_hand'], 'player_score' => calc_bj_score($game['p_hand']), 'dealer_score' => calc_bj_score($game['d_hand'])];
            
            if ($is_end) {
                unset($_SESSION['bj_game']);
                json_res(['status'=>'end', 'data'=>$data, 'msg'=>$msg, 'current_point'=>$me['point']]);
            } else {
                json_res(['status'=>'playing', 'data'=>$data]);
            }
            break;

        default: throw new Exception("알 수 없는 요청: $cmd");
    }

} catch (Exception $e) {
    json_res(['status'=>'error', 'message'=>$e->getMessage()]);
}

// ---------------------------------------------------------
// [헬퍼 함수]
// ---------------------------------------------------------

function calc_bj_score($hand) {
    $score = 0;
    foreach ($hand as $card) {
        if ($card >= 11 && $card <= 13) $score += 10;
        else if ($card == 1) $score += 1;
        else $score += $card;
    }
    return $score;
}


// 플레이어 상태이상 보정값 가져오는 헬퍼 (함수화)
function get_player_status_adjust($uid) {
    $my_status = sql_fetch_all("
        SELECT s.current_stage, i.stage_config 
        FROM School_Status_Active s 
        JOIN School_Status_Info i ON s.status_id = i.status_id 
        WHERE s.target_id = ?
    ", [$uid]);

    $st_atk = 0; $st_def = 0;
    foreach($my_status as $st) {
        $cfg = json_decode($st['stage_config'], true);
        $stage = $st['current_stage'];
        if(isset($cfg[$stage])) {
            $st_atk += ($cfg[$stage]['atk'] ?? 0);
            $st_def += ($cfg[$stage]['def'] ?? 0);
        }
    }
    return ['atk' => $st_atk, 'def' => $st_def];
}

// --- 전투 시작 공통 함수 (맨 아래에 위치) ---
function start_battle($room_id, $my_id, $input) {
    global $pdo;

    $room = sql_fetch("SELECT * FROM School_Battles WHERE room_id=?", [$room_id]);
    
    $players_list = [$room['host_id']];
    if ($room['guest_id']) $players_list[] = $room['guest_id'];

    $mob_live_data = [];
    $logs = [];

    // PVE (몬스터전)
    if ($room['target_id'] == 0) {
        $mob_count = isset($input['mob_count']) ? max(1, (int)$input['mob_count']) : rand(1, 3);
        $base_mob = sql_fetch("SELECT * FROM School_Monsters ORDER BY RAND() LIMIT 1");
        
        // 몬스터 없으면 슬라임 강제 생성 (에러 방지)
        if (!$base_mob) $base_mob = ['name'=>'슬라임', 'stats'=>json_encode(['stat_con'=>10, 'stat_str'=>5, 'stat_dex'=>5]), 'give_exp'=>10, 'give_point'=>10];

        for($i=0; $i<$mob_count; $i++) {
            $m_st = json_decode($base_mob['stats'], true);
            $m_calc = calc_battle_stats($m_st);
            if ($mob_count > 1) $m_calc['atk'] = floor($m_calc['atk'] * 0.9); // 다수 패널티

            $mob_live_data[] = [
                'id' => 'mob_'.$i, 'name' => $base_mob['name']." ".($i+1),
                'hp_max' => $m_calc['hp_max'], 'hp_cur' => $m_calc['hp_max'],
                'atk' => $m_calc['atk'], 'def' => $m_calc['def'], 'speed' => $m_calc['speed'],
                'is_dead' => false
            ];
        }
        $logs[] = ['msg' => "<b>{$base_mob['name']}</b> {$mob_count}마리가 나타났다!", 'type' => 'system'];
    } else {
        $logs[] = ['msg' => "⚔️ 결투가 시작되었습니다!", 'type' => 'system'];
    }

    // 플레이어 데이터 생성
    $players_data = [];
    $max_speed = 0;
    foreach($players_list as $pid) {
        $p_db = sql_fetch("SELECT * FROM School_Members WHERE id=?", [$pid]);
        $p_calc = calc_battle_stats($p_db);
        $p_calc['id'] = $pid;
        $p_calc['name'] = $p_db['name'];
        $p_calc['hp_cur'] = $p_db['hp_current'];
        $p_calc['is_dead'] = false;
        
        if ($p_calc['speed'] > $max_speed) $max_speed = $p_calc['speed'];
        $players_data[] = $p_calc;
    }

    $turn = ($max_speed >= ($mob_live_data[0]['speed'] ?? 0)) ? 'player' : 'enemy_ready';

    sql_exec("UPDATE School_Battles SET status='FIGHTING', mob_live_data=?, players_data=?, battle_log=?, turn_status=? WHERE room_id=?", 
        [json_encode($mob_live_data), json_encode($players_data), json_encode($logs), $turn, $room['room_id']]
    );

    json_out(['status'=>'success', 'start'=>true]);
}

// 스탯 계산 함수 (없으면 추가)
function calc_battle_stats($base, $add_atk=0, $add_def=0) {
    $str = $base['stat_str'] ?? 10;
    $dex = $base['stat_dex'] ?? 10;
    $con = $base['stat_con'] ?? 10;
    $int = $base['stat_int'] ?? 10;
    $luk = $base['stat_luk'] ?? 10;

    $atk = round(($str*0.4) + ($dex*0.3) + ($con*0.1)) + $add_atk;
    $def = round(($con*0.5) + ($dex*0.3)) + $add_def;
    
    return ['atk'=>max(1,$atk), 'def'=>max(0,$def), 'hp_max'=>$con*10, 'speed'=>$dex];
}
?>