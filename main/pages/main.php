<?php require_once '../includes/db.php'; 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRUA</title>
    <link rel="icon" href="../assets/images/채팅방.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/a11y-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/go.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <main id="content"></main>
        <header>
            <div class="main_title" onclick="location.href='#'" style="cursor: pointer;"></div>
            <div class="main_slog"></div>
            <nav>
                <a href="#/intro" style="cursor: pointer;">GRUA 소개</a>
                <a href="#/universry" style="cursor: pointer;">대학생활</a>
                <a href="#/creative" style="cursor: pointer;">창작학부</a>
                <a href="#/art" style="cursor: pointer;">미술학부</a>
                <a href="#/free" style="cursor: pointer;">자율전공학부</a>
            </nav>
            
            <div class="main_chat" onclick="location.href='#/guestbook'" style="cursor: pointer;"></div>
            <?php if ($is_admin || $is_user): ?>
                <div class="main_login" onclick="location.href='logout.php'" style="cursor: pointer;"></div>
            <?php else: ?>
                <div class="main_login" onclick="location.href='#/login'" style="cursor: pointer;"></div>
            <?php endif; ?>
        </header>


        <div id="chat-overlay" style="display: none;"></div>

    </div> 
    <script src="../assets/js/main.js"></script>
</body>
</html>