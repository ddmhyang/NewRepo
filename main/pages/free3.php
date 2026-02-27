<?php
require_once '../includes/db.php';
$gallery_type = '1';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM e_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="free_nav">
        <a style="font-family:'fre9'; font-size:20px; text-align: center; margin-top: 41px; margin-bottom:15px;">·심리학과</a>
        <a href="#/free" style="margin-bottom: 153px; cursor: pointer; margin-left:23px;">심리의이해</a>

        <a style="font-family:'fre9'; font-size:20px; text-align: center; margin-bottom:15px;">·자율전공학과</a>
        <a href="#/free2" style="margin-bottom: 153px; cursor: pointer; margin-left:23px;">자율전공</a>
        
        <a style="font-family:'fre9'; font-size:20px; text-align: center; margin-bottom:15px;">·미디어영상학과</a>
        <a href="#/free3" style="cursor: pointer; margin-left:23px; font-weight: bold;">플레이리스트</a>
    </div>

    <div class="free_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=e_gallery&type=<?php echo $gallery_type; ?>&return_page=free" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=e_gallery&id=<?php echo $post['id']; ?>&return_page=free" class="gallery-item">
                    <?php
                        $thumbnail_url = $post['thumbnail'] ?? '';
                        $style = !empty($thumbnail_url) 
                            ? "background-image: url('" . htmlspecialchars($thumbnail_url) . "');" 
                            : "background-color: #FAF4E2;";
                    ?>
                    <div class="item-thumbnail" style="<?php echo $style; ?>"></div>
                    <h3><?php echo htmlspecialchars($post['title']);?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>