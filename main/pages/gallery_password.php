<div class="password-form-container">
    <div class="password_content">
        <h2>비밀글입니다</h2>
        <p>비밀번호를 입력해주세요.</p>
        <form id="password-form" action="ajax_verify_password.php" method="post">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <input type="password" name="password" required>
            <button type="submit">확인</button>
        </form>
        <div id="password-error" style="color:red; margin-top:10px;"></div>
    </div>
</div>
<script>
$('#password-form').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                $('#password-error').text(response.message);
            }
        },
        error: () => $('#password-error').text('오류가 발생했습니다.')
    });
});
</script>