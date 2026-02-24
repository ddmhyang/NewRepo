<?php
require_once '../includes/db.php';
$gallery_type = '1';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM e_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="free_nav">
        <a href="#/free" style="cursor: pointer; font-weight: bold;">심리의 이해</a>
        <a href="#/free2" style="cursor: pointer;">자율</a>
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
                            : "background-color: #7078A750;";
                    ?>
                    <div class="item-thumbnail" style="<?php echo $style; ?>"></div>
                    <h3><?php echo htmlspecialchars($post['title']);?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>