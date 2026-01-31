<?php
// setup.php : 채팅 테이블 및 업로드 폴더 설정 추가
session_start();

// uploads 폴더 자동 생성
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

if (file_exists('config.php') && !isset($_GET['force'])) {
    header("Location: index.php");
    exit;
}

$msg = "";
if (isset($_POST['run_setup'])) {
    $host = $_POST['db_host'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    $dbname = $_POST['db_name'];
    $admin_pw = $_POST['admin_pw'];

    $config_code = "<?php\n";
    $config_code .= "define('MS_HOST', '$host');\n";
    $config_code .= "define('MS_DB', '$dbname');\n";
    $config_code .= "define('MS_USER', '$user');\n";
    $config_code .= "define('MS_PASS', '$pass');\n";
    $config_code .= "function get_conn() {\n";
    $config_code .= "    try {\n";
    $config_code .= "        \$pdo = new PDO(\"mysql:host=\".MS_HOST.\";dbname=\".MS_DB.\";charset=utf8mb4\", MS_USER, MS_PASS);\n";
    $config_code .= "        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
    $config_code .= "        return \$pdo;\n";
    $config_code .= "    } catch(PDOException \$e) { die(\"DB 연결 실패: \" . \$e->getMessage()); }\n";
    $config_code .= "}\n";
    $config_code .= "?>";

    file_put_contents('config.php', $config_code);

    // 2. DB 연결 및 테이블 생성
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // DB 생성
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        // (1) 멤버 테이블 (injury 추가됨)
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) UNIQUE, 
            name VARCHAR(50) NOT NULL,
            pw VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'student',
            level INT DEFAULT 1,
            point INT DEFAULT 1000,
            hp_current INT DEFAULT 100,
            hp_max INT DEFAULT 100,
            stat_str INT DEFAULT 5,
            stat_dex INT DEFAULT 5,
            stat_con INT DEFAULT 5,
            stat_int INT DEFAULT 5,
            stat_luk INT DEFAULT 5,
            img_profile VARCHAR(255),
            injury INT DEFAULT 0 COMMENT '부상 횟수 (4회 사망)',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

// [신규] 전투 채팅/로그 테이블
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Battle_Chat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            user_id INT DEFAULT 0 COMMENT '0이면 시스템 메시지',
            name VARCHAR(50),
            message TEXT,
            type VARCHAR(20) DEFAULT 'CHAT' COMMENT 'CHAT, SYSTEM, DAMAGE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 관리자 생성
        $chk = $pdo->prepare("SELECT count(*) FROM School_Members WHERE role='admin'");
        $chk->execute();
        if ($chk->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO School_Members (user_id, name, pw, role, point, stat_str, stat_dex, stat_con, stat_int, stat_luk) VALUES ('admin', '학생회장', ?, 'admin', 999999, 999, 999, 999, 999, 999)");
            $stmt->execute([$admin_pw]);
        }
        
        // 컬럼 패치
        try { $pdo->exec("ALTER TABLE School_Members ADD COLUMN injury INT DEFAULT 0"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE School_Battles ADD COLUMN target_id INT DEFAULT 0"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE School_Gamble_Config ADD COLUMN ratio FLOAT NOT NULL DEFAULT 2.0"); } catch(Exception $e) {}

        // (2) 아이템 정보
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Item_Info (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            type VARCHAR(20) NOT NULL, 
            price INT DEFAULT 100,
            descr TEXT,
            max_dur INT DEFAULT 1,
            img_icon VARCHAR(255),
            effect_data TEXT
        )");

        // (3) 인벤토리
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            item_id INT NOT NULL,
            count INT DEFAULT 1,
            current_dur INT DEFAULT 1,
            is_equipped TINYINT(1) DEFAULT 0,
            created_at DATETIME
        )");

// (5) 전투/방 (업데이트됨: host_ready, guest_ready 추가)
    sql_exec($db, "CREATE TABLE IF NOT EXISTS School_Battles (
        room_id INT AUTO_INCREMENT PRIMARY KEY,
        host_id INT NOT NULL,
        guest_id INT DEFAULT 0,
        target_id INT DEFAULT 0 COMMENT '0=몬스터, >0=유저',
        status VARCHAR(20) DEFAULT 'WAIT',
        host_ready TINYINT DEFAULT 0,
        guest_ready TINYINT DEFAULT 0,
        mob_live_data JSON,
        players_data JSON,
        battle_log JSON,
        turn_status VARCHAR(20) DEFAULT 'player',
        created_at DATETIME,
        updated_at DATETIME
    )");
        // (5) 상태이상 도감
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Status_Info (
            status_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            max_stage INT DEFAULT 3,
            stage_config TEXT,
            effect_script TEXT
        )");

        // (6) 활성 상태이상
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Status_Active (
            id INT AUTO_INCREMENT PRIMARY KEY,
            target_id INT NOT NULL,
            status_id INT NOT NULL,
            current_stage INT DEFAULT 1,
            created_at DATETIME,
            last_evolved_at DATETIME
        )");

        // (7) 상점 설정
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Shop_Config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            stock INT DEFAULT -1,
            refill_type VARCHAR(20) DEFAULT 'NONE', 
            refill_value VARCHAR(50) DEFAULT '',
            refill_amount INT DEFAULT 0,
            last_refilled_at DATETIME
        )");

        // (8) 로그
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Log (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(20),
            message TEXT,
            created_at DATETIME
        )");

        // (9) 도박 설정 (확률 컬럼 유지하되 기본값 1)
        $pdo->exec("CREATE TABLE IF NOT EXISTS School_Gamble_Config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            ratio FLOAT NOT NULL,
            probability INT NOT NULL DEFAULT 1
        )");

// [setup.php] School_Item_Info 테이블 생성 exec 바로 다음 줄에 추가

        // [추가됨] 기본 장착 아이템 데이터 삽입
        $pdo->exec("INSERT INTO School_Item_Info (name, type, price, descr, max_dur, img_icon, effect_data) VALUES 
            ('야구모자', 'HAT', 50, '기본적인 모자', 100, '<i class=\"fa-solid fa-hat-cowboy\"></i>', '{\"def\":1}'),
            ('마스크', 'FACE', 50, '먼지 방지용', 100, '<i class=\"fa-solid fa-mask\"></i>', '{\"luk\":1}'),
            ('체육복(상)', 'TOP', 100, '학교 체육복', 100, '<i class=\"fa-solid fa-shirt\"></i>', '{\"def\":2}'),
            ('체육복(하)', 'BOTTOM', 100, '학교 체육복', 100, '<i class=\"fa-solid fa-user\"></i>', '{\"speed\":1}'),
            ('목장갑', 'GLOVES', 30, '작업용 장갑', 50, '<i class=\"fa-solid fa-mitten\"></i>', '{\"str\":1}'),
            ('실내화', 'SHOES', 30, '하얀 실내화', 50, '<i class=\"fa-solid fa-shoe-prints\"></i>', '{\"speed\":2}')
        ");
        
        // [추가됨] 상점 기본 재고 등록
        $pdo->exec("INSERT INTO School_Shop_Config (item_id, stock) 
                    SELECT item_id, -1 FROM School_Item_Info 
                    WHERE item_id NOT IN (SELECT item_id FROM School_Shop_Config)");

        $msg = "<div class='success'>설치 완료! <a href='index.php'>메인으로 이동</a></div>";
    } catch(PDOException $e) {
        $msg = "<div class='error'>오류 발생: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시스템 설치</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #CE5961; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .success { color: green; text-align: center; font-weight: bold; }
        .error { color: red; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🏫 학교 설치 마법사</h2>
        <?=$msg?>
        <?php if(empty($msg)): ?>
        <form method="POST">
            <input type="hidden" name="run_setup" value="1">
            <label>DB 호스트</label>
            <input type="text" name="db_host" value="localhost" required>
            <label>DB 사용자</label>
            <input type="text" name="db_user" required>
            <label>DB 비밀번호</label>
            <input type="password" name="db_pass">
            <label>DB 이름</label>
            <input type="text" name="db_name" value="school_rpg" required>
            <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
            <label>관리자 비밀번호 설정</label>
            <input type="text" name="admin_pw" value="1234" required>
            <button type="submit">설치 시작</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>