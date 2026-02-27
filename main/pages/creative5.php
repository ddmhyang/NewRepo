<?php
require_once '../includes/db.php';
$gallery_type = '5';
$posts = $mysqli->query("SELECT id, title, thumbnail, is_private FROM b_gallery WHERE gallery_type = '$gallery_type' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="gallery-container">
    <div class="creative_nav">
        <a style="font-family:'fre9'; text-align: center; font-size:20px; margin-left: 0px;">·문예창작학과</a>
        <a href="#/creative" style="cursor: pointer;">사랑의 언어와 행동</a>
        <a href="#/creative2" style="cursor: pointer;">수인 인권과 법</a>
        <a href="#/creative3" style="cursor: pointer;">자기 경영과 어필</a>
        <a href="#/creative4" style="cursor: pointer;">세대 소통의 기술</a>
        <a href="#/creative5" style="cursor: pointer; font-weight: bold;">결혼과 가족</a>
        <a href="#/creative6" style="cursor: pointer;">비판적 사고와 표현</a>
        <a href="#/creative7" style="cursor: pointer;">영화의 이해</a>
        <a href="#/creative8" style="cursor: pointer;">구급과 안전교육</a>
        <a href="#/creative9" style="cursor: pointer;">글로벌 소통과 언어</a>
        <a href="#/creative10" style="cursor: pointer;">대학 합창 기약 이론</a>
    </div>

    <div class="creative_page">
        <?php if ($is_admin): ?>
            <a href="#/gallery_upload?table_name=b_gallery&type=<?php echo $gallery_type; ?>&return_page=creative" class="add-btn">새 글 작성</a>
            <?php endif; ?>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
                <a href="#/gallery_view?table_name=b_gallery&id=<?php echo $post['id']; ?>&return_page=creative" class="gallery-item">
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