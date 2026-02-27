<?php
require_once '../includes/db.php';
if (!$is_admin) { die("권한이 없습니다."); }

$post_id = intval($_GET['id'] ?? 0);

$table_name = $_GET['table_name'] ?? 'gallery';
$return_page = $_GET['return_page'] ?? 'gallery';

if ($post_id <= 0) { die("유효하지 않은 게시물입니다."); }

$allowed_tables = ['gallery', 'a_gallery', 'b_gallery', 'c_gallery', 'd_gallery', 'e_gallery', 'chan_gallery'];
if (!in_array($table_name, $allowed_tables)) { die("잘못된 게시판입니다."); }

$stmt = $mysqli->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) { die("게시물이 없습니다."); }
?>
<div class="form-page-container">
    <h2>게시물 수정</h2>
    <form class="ajax-form" action="ajax_save_gallery.php" method="post">
        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
        <input type="hidden" name="gallery_type" value="<?php echo htmlspecialchars($post['gallery_type']); ?>">
        
        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table_name); ?>">
        <input type="hidden" name="return_page" value="<?php echo htmlspecialchars($return_page); ?>">

        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="thumbnail">썸네일 (선택, 없으면 본문 첫 이미지 자동 등록)</label>
            <label for="thumbnail" class="file-upload-button">파일 선택</label>
            <input type="file" id="thumbnail" name="thumbnail" style="display: none;">
        </div>

        <div class="form-group">
            <label for="is_private">비밀글 설정</label>
            <input type="checkbox" id="is_private" name="is_private" value="1" <?php if($post['is_private']) echo 'checked'; ?>>
            <input type="password" id="password" name="password" placeholder="비밀번호 변경 시에만 입력" style="<?php if(!$post['is_private']) echo 'display:none;'; ?> margin-top: 10px;">
        </div>

        <div class="form-group">
            <label for="content">내용</label>
            <textarea class="summernote" name="content"><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>
        <button type="submit" class="btn-action">수정 완료</button>
        
        <a class="cancel_btn" href="#/gallery_view?table_name=<?php echo htmlspecialchars($table_name); ?>&id=<?php echo $post_id; ?>&return_page=<?php echo htmlspecialchars($return_page); ?>">취소</a>
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
                onPaste: function (e) {
                    var clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
                    var bufferText = clipboardData.getData('Text') || clipboardData.getData('text/plain');
                    bufferText = bufferText.trim();
                    
                    var ytRegex = /(?:youtu\.be\/|youtube\.com\/(?:embed\/|shorts\/|v\/|.*[?&]v=))([\w-]{11})/;
                    var match = bufferText.match(ytRegex);

                    if (match && match[1]) {
                        e.preventDefault(); 
                        var editor = $(this);
                        
                        setTimeout(function () {
                            var wrapperHtml = '<div style="width: 50%; margin: 0 auto 15px auto;">' +
                                            '<div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; overflow: hidden;">' +
                                            '<iframe src="https://www.youtube.com/embed/' + match[1] + '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" allowfullscreen></iframe>' +
                                            '</div></div>';
                            
                            editor.summernote('insertNode', $(wrapperHtml)[0]);
                            editor.summernote('insertNode', $('<p><br></p>')[0]); 
                        }, 10);
                    }
                },
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