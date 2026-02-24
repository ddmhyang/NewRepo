<?php require_once '../includes/db.php'; 
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>title</title>
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
                <a href="#/social" style="cursor: pointer;">문예창작학과</a>
                <a href="#/chem" style="cursor: pointer;">화확과</a>
                <a href="#/manga" style="cursor: pointer;">만화과</a>
                <a href="#/art" style="cursor: pointer;">시각디자인과</a>
                <a href="#/video" style="cursor: pointer;">미디어영상학과</a>
                <a href="#/free" style="cursor: pointer;">자율전공학과</a>
            </nav>
            
            <div class="main_chat" onclick="location.href='#chat'" style="cursor: pointer;"></div>
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