<?php
// admin_gamble.php : 룰렛 설정 (이름 입력 제거 버전)
require_once 'common.php';

// 권한 체크
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('관리자만 접근 가능합니다.'); location.replace('index.php');</script>";
    exit;
}

// 테이블 생성 (기존 유지)
try {
    sql_exec("CREATE TABLE IF NOT EXISTS School_Gamble_Config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        game_type VARCHAR(50) DEFAULT '기본',
        name VARCHAR(50) NOT NULL,
        ratio FLOAT NOT NULL,
        probability INT NOT NULL
    )");
    // 기존 테이블 호환성을 위해 컬럼 체크 (생략 가능)
} catch(Exception $e) {}

// 1. 룰렛 항목 추가
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $type = trim($_POST['game_type']);
    $ratio = (float)$_POST['ratio'];
    $prob = to_int($_POST['prob']);
    
    // [변경] 이름을 입력받지 않고 배율로 자동 생성
    $name = $ratio . "배";
    
    if ($prob > 0) {
        if(!$type) $type = '기본';
        sql_exec("INSERT INTO School_Gamble_Config (game_type, name, ratio, probability) VALUES (?, ?, ?, ?)", 
            [$type, $name, $ratio, $prob]
        );
        echo "<script>location.replace('admin_gamble.php');</script>";
        exit;
    }
}

// 2. 항목 삭제
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = to_int($_POST['id']);
    sql_exec("DELETE FROM School_Gamble_Config WHERE id=?", [$id]);
    echo "<script>location.replace('admin_gamble.php');</script>";
    exit;
}

// 목록 조회
$list = sql_fetch_all("SELECT * FROM School_Gamble_Config ORDER BY game_type ASC, ratio ASC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>도박장 설정</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --bg: #F0F2F5; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { margin: 0; color: #333; display: flex; align-items: center; gap: 10px; }
        .back-btn { font-size: 14px; background: #999; border: none; color: white; border-radius: 5px; padding: 8px 15px; cursor: pointer; font-weight: bold; }

        /* 입력 폼 */
        .form-box { background: white; padding: 20px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; } /* 3열로 변경 */
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #555; }
        input { padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 100%; box-sizing: border-box; font-family: 'Pretendard'; }
        
        .btn-add { padding: 15px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; margin-top: 10px; }
        .btn-add:hover { opacity: 0.9; }

        /* 테이블 */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; color: #555; font-size: 14px; }
        .type-badge { display: inline-block; padding: 4px 8px; background: #eee; border-radius: 5px; font-size: 12px; font-weight: bold; color: #555; }
        
        .btn-del { background: #fee; color: #e74c3c; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-del:hover { background: #fdd; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fa-solid fa-dice"></i> 룰렛 설정</h2>
        <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
    </div>

    <div class="form-box">
        <h3 style="margin-top:0;">새 항목 추가</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-grid">
                <div>
                    <label>게임 종류 (Type)</label>
                    <input type="text" name="game_type" placeholder="예: 기본" value="기본" required>
                </div>
                <div>
                    <label>배율 (x)</label>
                    <input type="text" name="ratio" placeholder="예: 2, -1, 0.5" required>
                </div>
                <div>
                    <label>확률 (가중치)</label>
                    <input type="number" name="prob" placeholder="예: 10" value="1" required>
                </div>
            </div>
            
            <button type="submit" class="btn-add">추가하기</button>
            <div style="font-size:12px; color:#888; margin-top:10px; text-align:center;">
                * 항목 이름은 배율에 따라 자동 생성됩니다 (예: "2배")
            </div>
        </form>
    </div>

    <div style="font-size:14px; color:#666; margin-bottom:10px; margin-left:5px;">등록된 룰렛 항목 목록</div>
    
    <table>
        <thead>
            <tr>
                <th width="20%">게임 종류</th>
                <th>배율 (결과)</th>
                <th width="20%">확률</th>
                <th width="10%">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($list as $row): ?>
            <tr>
                <td><span class="type-badge"><?=$row['game_type']?></span></td>
                <td>
                    <b style="font-size:16px; color: <?=$row['ratio'] >= 0 ? 'blue' : 'red'?>;">
                        x<?=$row['ratio']?>
                    </b>
                </td>
                <td><?=$row['probability']?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('삭제하시겠습니까?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=$row['id']?>">
                        <button type="submit" class="btn-del"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$list): ?>
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">등록된 항목이 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>