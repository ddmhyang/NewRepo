<?php
require_once '../includes/db.php';
$gallery_type = '1';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM a_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="chem_nav">
        <a href="#/chem" style="cursor: pointer; font-weight: bold;">그림</a>
        <a href="#/chem2" style="cursor: pointer;">낙서</a>
        <a href="#/chem3" style="cursor: pointer;">도트</a>
    </div>

    <div class="chem_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=a_gallery&type=<?php echo $gallery_type; ?>&return_page=chem" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=a_gallery&id=<?php echo $post['id']; ?>&return_page=chem" class="gallery-item">
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