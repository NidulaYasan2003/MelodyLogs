<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT blogPost.*, user.username FROM blogPost JOIN user ON blogPost.user_id = user.id ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>

<div class="hero">
    <h1>Welcome to MelodyLogs</h1>
    <p>Discover vocal stories, studio journals, and music updates from independent singers.</p>
</div>

<div class="posts-grid">
    <?php if (empty($posts)): ?>
        <p>No blog posts found yet. Be the first singer to post!</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <h2><a href="post.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
                <div class="meta">
                    <span>By <strong><?= htmlspecialchars($post['username']) ?></strong></span> | 
                    <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                </div>
                <p><?= htmlspecialchars(substr($post['content'], 0, 150)) ?>...</p>
                <a href="post.php?id=<?= $post['id'] ?>" class="read-more">Read Full Post &rarr;</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>