<?php
require_once '../includes/db.php';
if ($is_admin) {
    echo "<script>window.location.hash = '#/main';</script>";
    exit;
}
?>
<div class="login-container">
    <div class="login-form">
        <form id="login-form" action="ajax_login.php" method="post">
            <input type="text" name="username" required>
            <input type="password" name="password" required>
            <button type="submit">로그인</button>
            <div id="login-error" style="color:red; margin-top:10px;"></div>
        </form>
    </div>
</div>
<script>
$('#login-form').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.location.href = 'main.php'; 
            } else {
                $('#login-error').text(response.message);
            }
        },
        error: () => $('#login-error').text('로그인 중 서버 오류가 발생했습니다.')
    });
});
</script>