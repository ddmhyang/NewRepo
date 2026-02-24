<?php
require_once '../includes/db.php';
$gallery_type = '4';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="social_nav">
        <a href="#/social" style="cursor: pointer; font-weight: bold;">영곰</a>
        <a href="#/social2" style="cursor: pointer;">필곰</a>
        <a href="#/social3" style="cursor: pointer;">도곰</a>
        <a href="#/social4" style="cursor: pointer; font-weight: bold;">다자</a>
        <a href="#/social5" style="cursor: pointer;">썰</a>
    </div>

    <div class="social_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=gallery&type=<?php echo $gallery_type; ?>&return_page=social" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=gallery&id=<?php echo $post['id']; ?>&return_page=social" class="gallery-item">
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