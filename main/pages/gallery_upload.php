<?php
require_once '../includes/db.php';
if (!$is_admin) { die("권한이 없습니다."); }
$table_name = $_GET['table_name'] ?? 'gallery';
$gallery_type = $_GET['type'] ?? '1';
$return_page = $_GET['return_page'] ?? 'gallery';
?>
<div class="form-page-container">
    <h2>새 글 작성</h2>
    <form class="ajax-form" action="ajax_save_gallery.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table_name); ?>">
        <input type="hidden" name="gallery_type" value="<?php echo htmlspecialchars($gallery_type); ?>">
        <input type="hidden" name="return_page" value="<?php echo htmlspecialchars($return_page); ?>">
        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
            <label for="thumbnail">썸네일 (선택, 없으면 본문 첫 이미지 자동 등록)</label>
            <label for="thumbnail" class="file-upload-button">파일 선택</label>
            <input type="file" id="thumbnail" name="thumbnail" style="display: none;">
        </div>
        <div class="form-group">
            <label><input type="checkbox" id="is_private" name="is_private" value="1"> 비밀글</label>
            <input type="password" id="password" name="password" placeholder="비밀번호 (비밀글 체크 시 입력)" style="display:none;">
        </div>
        <div class="form-group">
            <label for="content">내용</label>
            <textarea class="summernote" name="content"></textarea>
        </div>
        <button type="submit">저장하기</button>
        <a class="cancel_btn" href="#/<?php echo htmlspecialchars($return_page); ?>">취소</a>
    </form>
</div>
<script>
    $(document).ready(function() {
        var codeBlockButton = function (context) {
            var ui = $.summernote.ui;
            var button = ui.button({
                contents: '<i class="fa fa-code"/> Code Block',
                tooltip: 'Insert Code Block',
                click: function () {
                    var node = $('<pre><code class="html"></code></pre>')[0];
                    context.invoke('editor.insertNode', node);
                }
            });
            return button.render();
        }
        $('.summernote').summernote({
            height: 400,
            callbacks: {
                onImageUpload: async function(files) {
                    let sortedFiles = Array.from(files).sort((a, b) => 
                        a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'})
                    );

                    for (let i = 0; i < sortedFiles.length; i++) {
                        await uploadSummernoteImage(sortedFiles[i], $(this));
                    }
                }
            },
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['codeview', 'help']]
            ]
        });
        $('#is_private').on('change', function() {
            $('#password').toggle(this.checked).prop('required', this.checked);
        });
    });
</script>