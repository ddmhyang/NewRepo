<?php
// log.php : 내 활동 기록
require_once 'common.php';

if (!isset($_SESSION['uid'])) { header("Location: index.php"); exit; }
$my_id = $_SESSION['uid'];

$logs = sql_fetch_all("SELECT * FROM School_Log WHERE user_id = ? ORDER BY log_id DESC LIMIT 100", [$my_id]);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>활동 기록</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.8/dist/web/static/pretendard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #CE5961; --bg: #F0F2F5; }
        body { font-family: 'Pretendard', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn { background: #ddd; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .log-list { list-style: none; padding: 0; margin: 0; }
        .log-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .log-item:last-child { border-bottom: none; }
        .log-type { font-size: 11px; padding: 3px 6px; border-radius: 4px; background: #eee; color: #777; margin-right: 8px; font-weight: bold; }
        .log-msg { font-size: 15px; }
        .log-time { font-size: 12px; color: #999; min-width: 60px; text-align: right; }
        .type-POINT { background: #e8f6f3; color: #16a085; }
        .type-ITEM { background: #fff8e1; color: #f39c12; }
        .type-BATTLE { background: #ffebee; color: #c0392b; }
        .type-GAMBLE { background: #f3e5f5; color: #8e44ad; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;"><i class="fa-solid fa-clock-rotate-left"></i> 활동 기록</h2>
        <button class="back-btn" onclick="location.href='index.php'">메인으로</button>
    </div>
    <ul class="log-list">
        <?php foreach($logs as $log): ?>
        <li class="log-item">
            <div>
                <span class="log-type type-<?=$log['type']?>"><?=$log['type']?></span>
                <span class="log-msg"><?=$log['message']?></span>
            </div>
            <div class="log-time"><?=date('m-d H:i', strtotime($log['created_at']))?></div>
        </li>
        <?php endforeach; ?>
        <?php if(!$logs): ?><li style="text-align:center; padding:30px; color:#999;">기록이 없습니다.</li><?php endif; ?>
    </ul>
</div>
</body>
</html>