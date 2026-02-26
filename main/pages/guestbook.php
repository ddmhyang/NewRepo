<?php
require_once '../includes/db.php';
$entries = $mysqli->query("SELECT * FROM guestbook ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="guestbook-container">
    <div class="guest_img1"></div>
    <div class="guest_img2"></div>
    <div class="guest_img3"></div>
    <div class="guest_img4" onclick="location.href='#/black'" style="cursor: pointer;"></div>
    <div class="guest_box">
        <div class="guest_form_box">
            <form class="ajax-form" action="ajax_save_guestbook.php" method="post">
                <div class="form-group">
                    <textarea name="content" required></textarea>
                </div>
                <button type="submit" class="btn btn-secondary-guest">Submit</button>
            </form>
        </div>

        <div class="guestbook-entries">
            <?php foreach ($entries as $entry): ?>
                <?php
                    $entry_class = $is_admin ? 'entry admin-entry' : 'entry';
                ?>
                <div class="<?php echo $entry_class; ?>" id="entry-<?php echo $entry['id']; ?>" data-id="<?php echo $entry['id']; ?>">
                    
                    <div class="entry-icon"></div>

                    <div class="entry-content-box">
                        <p><?php echo nl2br(htmlspecialchars($entry['content'])); ?></p>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php if ($is_admin):?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let pressTimer;

    $('.admin-entry').on('mousedown touchstart', function() {
        const entryElement = $(this);
        const entryId = entryElement.data('id');

        pressTimer = window.setTimeout(function() {
            if (confirm('이 방명록을 정말로 삭제하시겠습니까?')) {
                $.ajax({
                    url: 'ajax_delete_guestbook.php',
                    type: 'POST',
                    data: { id: entryId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#entry-' + entryId).fadeOut(500, function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.message || '삭제에 실패했습니다.');
                        }
                    },
                    error: function() {
                        alert('서버와 통신 중 오류가 발생했습니다.');
                    }
                });
            }
        }, 1000);
    }).on('mouseup mouseleave touchend', function() {
        clearTimeout(pressTimer);
    });
});
</script>
<?php endif; ?>