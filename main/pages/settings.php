<?php
require_once '../includes/db.php';
if (!$is_admin) { die("관리자만 접근 가능합니다."); }

$settings_result = $mysqli->query("SELECT * FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="settings-container">
    <h2>사이트 설정</h2>
    <form class="ajax-form" action="ajax_save_settings.php" method="post" enctype="multipart/form-data">
        <hr>
        <div class="form-group">
            <label for="index_button_image">입장 버튼 이미지</label>
            <label for="index_button_image" class="file-upload-button">파일 선택</label>
            <input type="file" id="index_button_image" name="index_button_image" style="display: none;">
            <p>현재 이미지: <?php echo basename($settings['index_button_image']); ?></p>
        </div>
        <hr>
        <div class="form-group">
            <label for="main_background">메인 배경화면</label>
            <label for="main_background" class="file-upload-button">파일 선택</label>
            <input type="file" id="main_background" name="main_background" style="display: none;">
            <p style="font-family: Fre1; font-size:16px">현재 이미지: <?php echo basename($settings['main_background']); ?></p>
        </div>
        <hr>
        <div class="form-group">
            <label for="chat_background" style="font-family: Fre5;">채팅창 배경화면</label>
            <label for="chat_background" class="file-upload-button">파일 선택</label>
            <input type="file" id="chat_background" name="chat_background" style="display: none;">
            <p style="font-family: Fre1; font-size:16px">현재 이미지: <?php echo basename($settings['chat_background']); ?></p>
        </div>
        <hr>
        <div class="form-group">
            <label for="character1_name">캐릭터 1 이름</label>
            <input type="text" id="character1_name" name="character1_name" value="<?php echo htmlspecialchars($settings['character1_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="character2_name">캐릭터 2 이름</label>
            <input type="text" id="character2_name" name="character2_name" value="<?php echo htmlspecialchars($settings['character2_name']); ?>" required>
        </div>
        <hr>
        <div class="form-group">
            <label for="character1_image">캐릭터 1 프로필 사진</label>
            <label for="character1_image" class="file-upload-button">파일 선택</label>
            <input type="file" id="character1_image" name="character1_image" style="display: none;">
            <p>현재 이미지: <img src="<?php echo htmlspecialchars($settings['character1_image']); ?>" height="50"></p>
        </div>
        <div class="form-group">
            <label for="character2_image">캐릭터 2 프로필 사진</label>
            <label for="character2_image" class="file-upload-button">파일 선택</label>
            <input type="file" id="character2_image" name="character2_image" style="display: none;">
            <p>현재 이미지: <img src="<?php echo htmlspecialchars($settings['character2_image']); ?>" height="50"></p>
        </div>
        <hr>
        <button class="submit_btn" type="submit">설정 저장</button>
    </form>
</div>  