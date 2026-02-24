<?php require_once '../includes/db.php'; 
$settings_result = $mysqli->query("SELECT * FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$main_bg = $settings['main_background'] ?? '../assets/images/default_background.png';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>title</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/a11y-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/go.min.js"></script>
</head>
<body>
    <div class="container" style="background-image: url('<?php echo htmlspecialchars($main_bg); ?>');">
        <header>
            <nav>
                <a href="#/main_content">Main</a>
                <a href="#/chanlan">ChanLan</a>
                <a href="#/chat">Chat</a>
                <a href="#/gallery">Gallery</a>
                <?php if ($is_admin): ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="#/login">Login</a>
                <?php endif; ?>
            </nav>
        </header>


        <audio id="music-player" loop>
            <source src="../assets/bgm/music.mp3" type="audio/mpeg">
            오디오 오류. 문의주세요.
        </audio>

        <div class="dday">
            <?php
                $target_date = new DateTime("2024-02-17");
                $current_date = new DateTime();

                $interval = $current_date->diff($target_date);

                $d_day = $interval->days;

                if ($current_date > $target_date) {
                    echo "D+" . $d_day+1;
                } else {
                    echo "D-" . $d_day;
                }
            ?>
        </div>

        <main id="content"></main>
        <div id="chat-overlay" style="display: none;"></div>

    </div> 
    <script src="../assets/js/main.js"></script>
</body>
</html>