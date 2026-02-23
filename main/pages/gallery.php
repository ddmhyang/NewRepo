<?php
require_once '../includes/db.php';
$gallery_type = 'gallery';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <h2><?php echo ucfirst($gallery_type); ?></h2>
    <?php if ($is_admin): ?><a href="#/gallery_upload?type=<?php echo $gallery_type; ?>" class="add-btn">새 글 작성</a><?php endif; ?>
    <div class="gallery-grid">
        <?php foreach ($posts as $post): ?>
            <a href="#/gallery_view?id=<?php echo $post['id']; ?>" class="gallery-item">
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