<?php
require_once '../includes/db.php';
if ($is_admin) {
    echo "<script>window.location.hash = '#/main';</script>";
    exit;
}
?>
<div class="login-container">
    <div class="login-form">
        <form id="signup-form" action="ajax_signup.php" method="post">
            <input type="text" name="username" placeholder="가입할 아이디" required>
            <input type="password" name="password" placeholder="가입할 비밀번호" required>
            
            <button type="submit">가입하기</button>
            <button type="button" onclick="window.location.hash='#/login'">취소 (로그인으로)</button>
            <div id="signup-error" style="color:red; margin-top:10px;"></div>
        </form>
    </div>
</div>
<script>
$('#signup-form').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('가입이 완료되었습니다! 로그인해주세요.');
                window.location.hash = '#/login';
            } else {
                $('#signup-error').text(response.message);
            }
        },
        error: () => $('#signup-error').text('가입 중 서버 오류가 발생했습니다.')
    });
});
</script>