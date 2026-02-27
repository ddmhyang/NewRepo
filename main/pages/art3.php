<?php
require_once '../includes/db.php';
$gallery_type = '3';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM c_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="art_nav">
        <a style="font-family:'fre9'; font-size:20px; text-align: center; margin-top: 41px; margin-bottom:15px;">·회화과</a>
        <a href="#/art" style="margin-bottom: 13px; cursor: pointer; margin-left:23px;">회화</a>
        <a href="#/art2" style="margin-bottom: 121px; cursor: pointer; margin-left:23px;">도트</a>
        
        <a style="font-family:'fre9'; font-size:20px; text-align: center; margin-bottom:15px;">·만화과</a>
        <a href="#/art3" style="cursor: pointer; margin-left:23px; font-weight: bold;">스토리</a>
    </div>

    <div class="art_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=c_gallery&type=<?php echo $gallery_type; ?>&return_page=art" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=c_gallery&id=<?php echo $post['id']; ?>&return_page=art" class="gallery-item">
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