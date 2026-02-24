<?php
require_once '../includes/db.php';
$gallery_type = 'trpg';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM chan_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="timeline-container">
    <?php if ($is_admin): ?><a href="#/gallery_upload?type=<?php echo $gallery_type; ?>" class="add-btn">새 글 작성</a><?php endif; ?>
    <div class="timeline-wrapper">
        <div class="timeline-events">
            <div class="timeline-line"></div>
            <?php foreach ($posts as $index => $post): ?>
                <?php
                    $position_class = ($index % 2 == 0) ? 'top' : 'bottom';
                    
                    $thumbnail_url = $post['thumbnail'] ?? '';
                    $style = !empty($thumbnail_url) 
                        ? "background-image: url('" . htmlspecialchars($thumbnail_url) . "');" 
                        : ""; 
                ?>
                <div class="timeline-item <?php echo $position_class; ?>">
                    <a href="#/gallery_view?id=<?php echo $post['id']; ?>">
                        <div class="item-thumbnail" style="<?php echo $style; ?>"></div>
                        <div class="item-text">
                            <h3><?php echo htmlspecialchars($post['title']);?></h3>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>