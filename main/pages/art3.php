<?php
require_once '../includes/db.php';
$gallery_type = '3';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM c_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="art_nav">
        <div style="font-weight: 500;">·회화과
            <a href="#/art" style="display: block; margin-bottom: 10px; cursor: pointer;">회화</a>
            <a href="#/art2" style="display: block; margin-bottom: 10px; cursor: pointer;">도트</a>
        </div>
        
        <div style="font-weight: 500;">·만화과
            <a href="#/art3" style="display: block; margin-bottom: 10px; cursor: pointer;">스토리</a>
        </div>
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
                            : "background-color: #7078A750;";
                    ?>
                    <div class="item-thumbnail" style="<?php echo $style; ?>"></div>
                    <h3><?php echo htmlspecialchars($post['title']);?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>