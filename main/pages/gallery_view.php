<?php
require_once '../includes/db.php';

$post_id = intval($_GET['id'] ?? 0);
$table_name = $_GET['table_name'] ?? 'gallery';
$return_page = $_GET['return_page'] ?? 'gallery';

$allowed_tables = ['gallery', 'a_gallery', 'b_gallery', 'c_gallery', 'd_gallery', 'e_gallery', 'chan_gallery'];
if (!in_array($table_name, $allowed_tables)) { die("잘못된 게시판입니다."); }

if ($post_id <= 0) { die("유효하지 않은 게시물 ID입니다."); }

$stmt = $mysqli->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) { die("게시물이 존재하지 않습니다."); }

$can_view = false;
if ($post['is_private'] == 0 || $is_admin || (isset($_SESSION['post_access'][$post_id]) && (time() - $_SESSION['post_access'][$post_id] < 1800))) {
    $can_view = true;
} else {
    unset($_SESSION['post_access'][$post_id]);
}

if (!$can_view) {
    include 'gallery_password.php';
    exit;
}
?>
<div class="view-container">
    <h1><?php echo htmlspecialchars($post['title']);?></h1>
    
    <div class="post-meta">
        <?php if ($is_admin): ?>
            <a href="#/gallery_edit?table_name=<?php echo htmlspecialchars($table_name); ?>&id=<?php echo $post_id; ?>&return_page=<?php echo htmlspecialchars($return_page); ?>" class="btn-action">수정</a>
            <button class="btn-action delete-btn" data-id="<?php echo $post_id; ?>" data-table="<?php echo htmlspecialchars($table_name); ?>" data-return="<?php echo htmlspecialchars($return_page); ?>">삭제</button>
        <?php endif; ?>
    </div>
    
    <hr>
    
    <div class="post-date">작성일: <?php echo date("Y-m-d", strtotime($post['created_at'])); ?></div>
    
    <div class="post-content">
        <?php echo $post['content']; ?>
    </div>
    
    <div class="post-actions">
        <a href="#/<?php echo htmlspecialchars($return_page); ?>" class="btn-back-to-list">목록으로</a>
    </div>
</div>