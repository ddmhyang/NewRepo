<?php
require_once '../includes/db.php';
$gallery_type = '3';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM a_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="universry_nav">
        <a href="#/universry" style="cursor: pointer; margin-top: 78px;">공지사항</a>
        <a href="#/universry2" style="cursor: pointer;">학생회</a>
        <a href="#/universry3" style="cursor: pointer; font-weight: bold;">기숙사</a>
        <a href="#/universry4" style="cursor: pointer;">교내 분실물</a>
    </div>

    <div class="universry_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=a_gallery&type=<?php echo $gallery_type; ?>&return_page=universry" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=a_gallery&id=<?php echo $post['id']; ?>&return_page=universry" class="gallery-item">
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