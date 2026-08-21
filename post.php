<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT blogPost.*, user.username FROM blogPost JOIN user ON blogPost.user_id = user.id WHERE blogPost.id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) { die("Post not found."); }

// Calculate Reading Time (avg 200 words per minute)
$wordCount = str_word_count(strip_tags($post['content']));
$readTime = max(1, ceil($wordCount / 200));
?>

<div class="single-post-container">
    <img src="https://picsum.photos/seed/<?= $post['id'] + 100 ?>/1200/600" class="single-hero-img" alt="Featured Image">

    <div class="single-post">
        <span class="category-tag">Studio Log</span>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        
        <div class="meta-author" style="margin-bottom: 30px; border-bottom: 1px solid #27272a; padding-bottom: 20px;">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=random&color=fff" class="avatar" style="width:50px; height:50px;" alt="Avatar">
            <div class="meta-info">
                <strong style="font-size: 1.1rem;"><?= htmlspecialchars($post['username']) ?></strong>
                <span><?= date('M d, Y', strtotime($post['created_at'])) ?> • ⏱️ <?= $readTime ?> min read</span>
            </div>
            
            <!-- Fake Social Share Buttons for Realism -->
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <span style="cursor:pointer; background:#1da1f2; padding:5px 10px; border-radius:5px; font-size:0.8rem;">Share</span>
            </div>
        </div>
        
        <!-- Strip dangerous tags, but allow formatting tags from TinyMCE -->
        <div class="post-content">
            <?= strip_tags($post['content'], '<p><br><strong><em><u><ul><li><ol><h1><h2><h3><h4><blockquote><a>') ?>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
            <div class="post-actions" style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #27272a;">
                <a href="editor.php?id=<?= $post['id'] ?>" class="btn" style="margin-right: 10px;">Edit Post</a>
                <a href="delete.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this blog?')">Delete Post</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>