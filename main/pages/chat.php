<?php
require_once '../includes/db.php';

$settings_result = $mysqli->query("SELECT * FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$char1_name = $settings['character1_name'] ?? '1';
$char2_name = $settings['character2_name'] ?? '2';
$char1_img = $settings['character1_image'] ?? '/assets/img/default_1.png';
$char2_img = $settings['character2_image'] ?? '/assets/img/default_2.png';

$messages = $mysqli->query("SELECT * FROM chat ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="chat-container">
    <div class="chat-window" id="message-list">
        <div class="chat-header"></div>

        <?php foreach ($messages as $msg): ?>
        <?php
            $is_char2 = (strtolower($msg['character_name']) === strtolower($char2_name));
            $message_side_class = $is_char2 ? 'sent' : 'received';
            $profile_image = $is_char2 ? $char2_img : $char1_img;
        ?>
        <div
            class="message-row <?php echo $message_side_class; ?>"
            data-id="<?php echo $msg['id']; ?>">
            <div
                class="profile-pic"
                style="background-image: url('..<?php echo htmlspecialchars($profile_image); ?>');"></div>
            <div class="message-bubble">
                <div class="character-name"><?php echo htmlspecialchars($msg['character_name']); ?></div>
                <p class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($is_admin): ?>
    <div class="chat-input-area">
        <form id="chat-form" action="ajax_save_chat.php" method="post">
            <select name="character_name">
                <option value="<?php echo htmlspecialchars($char1_name); ?>"><?php echo htmlspecialchars($char1_name); ?></option>
                <option value="<?php echo htmlspecialchars($char2_name); ?>"><?php echo htmlspecialchars($char2_name); ?></option>
            </select>
            <input
                type="text"
                name="message"
                placeholder="메시지 입력..."
                autocomplete="off"
                required="required">
            <button type="submit">전송</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        var chatWindow = $('#message-list');
        if (chatWindow.length > 0) {
            chatWindow.scrollTop(chatWindow[0].scrollHeight);
        }

        $('#chat-form').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#chat-overlay').load('chat.php');
                    } else {
                        alert('메시지 전송 실패: ' + response.message);
                    }
                }
            });
        });

        <?php if ($is_admin): ?>
        let pressTimer;
        $('#chat-overlay')
            .on(
                'mousedown touchstart',
                '.message-row',
                function (e) {
                    let messageRow = $(this);
                    pressTimer = window.setTimeout(function () {
                        if (confirm('이 메시지를 삭제하시겠습니까?')) {
                            const messageId = messageRow.data('id');
                            $.ajax({
                                url: 'ajax_delete_chat.php',
                                type: 'POST',
                                data: {
                                    id: messageId
                                },
                                dataType: 'json',
                                success: function (response) {
                                    if (response.success) {
                                        messageRow.fadeOut(300, function () {
                                            $(this).remove();
                                        });
                                    } else {
                                        alert('삭제 실패: ' + response.message);
                                    }
                                }
                            });
                        }
                    }, 800);
                }
            )
            .on(
                'mouseup mouseleave touchend',
                '.message-row',
                function () { 
                    clearTimeout(pressTimer);
                }
            );
        <?php endif; ?>
    });
</script>