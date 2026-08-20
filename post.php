<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT blogPost.*, user.username FROM blogPost JOIN user ON blogPost.user_id = user.id WHERE blogPost.id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) { die("Post not found."); }
?>

<div class="single-post">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div class="meta">
        <span>By <strong><?= htmlspecialchars($post['username']) ?></strong></span> | 
        <span>Posted on <?= date('F d, Y', strtotime($post['created_at'])) ?></span>
    </div>
    
    <div class="post-content">
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </div>

    <!-- Authorization Check: Only owner can edit or delete -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
        <div class="post-actions">
            <a href="editor.php?id=<?= $post['id'] ?>" class="btn">Edit Post</a>
            <a href="delete.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this blog?')">Delete Post</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>