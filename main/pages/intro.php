<?php
require_once '../includes/db.php';
$page_slug = 'intro1';
$stmt = $mysqli->prepare("SELECT content FROM pages WHERE slug = ?");
$stmt->bind_param("s", $page_slug);
$stmt->execute();
$page_content = $stmt->get_result()->fetch_assoc()['content'] ?? '<p>콘텐츠가 없습니다.</p>';
$stmt->close();
?>
<div class="gallery-container">
    <div class="intro_nav">
        <a href="#/intro" style="cursor: pointer; font-weight: bold; margin-top: 78px;">GRUA 소개</a>
        <a href="#/intro2" style="cursor: pointer;">비전과 미션</a>
        <a href="#/intro3" style="cursor: pointer;">캠퍼스 안내</a>
    </div>

    <div class="intro_page" style="background-color: #FAF4E2">
        <div id="view-mode">
            <div class="content-display"><?php echo $page_content; ?></div>
            <?php if ($is_admin): ?><button class="edit-btn">수정하기</button><?php endif; ?>
        </div>
        <?php if ($is_admin): ?>
        <div id="edit-mode" style="display:none;">
            <form class="ajax-form" action="ajax_save_page.php" method="post">
                <input type="hidden" name="slug" value="<?php echo $page_slug; ?>">
                <textarea class="summernote" name="content"><?php echo htmlspecialchars($page_content); ?></textarea>
                <button type="submit">저장하기</button> 
                <button type="button" class="cancel-btn">취소</button>
            </form>
        </div>
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
        
        $('.edit-btn').on('click', function() {
            $('#view-mode').hide();
            $('#edit-mode').show();
            
            $('.summernote').summernote({
                height: 400,
                focus: true,
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
                    onImageUpload: function(files) {
                        uploadSummernoteImage(files[0], $(this));
                    }
                },
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                    ['mybutton', ['codeBlock']]
                ],
                buttons: {
                    codeBlock: codeBlockButton
                }
            });
        });

        $('.cancel-btn').on('click', function() {
            $('.summernote').summernote('destroy');
            $('#edit-mode').hide();
            $('#view-mode').show();
        });
    });
    </script>
    <?php endif; ?>
</div>