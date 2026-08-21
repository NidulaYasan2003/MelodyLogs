<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT blogPost.*, user.username FROM blogPost JOIN user ON blogPost.user_id = user.id ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>

<div class="hero">
    <h1>MelodyLogs Studio</h1>
    <p>Discover vocal stories, studio journals, and music updates from independent singers worldwide.</p>
</div>

<div class="posts-grid">
    <?php if (empty($posts)): ?>
        <p style="text-align:center; width:100%; color:#a1a1aa;">No studio logs found yet. Be the first singer to post!</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <!-- Auto-generated cover image based on post ID -->
                <img src="https://picsum.photos/seed/<?= $post['id'] + 100 ?>/600/400" alt="Cover" class="post-thumbnail">
                
                <div class="post-card-content">
                    <div class="meta-author">
                        <!-- Auto-generated Avatar -->
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=random&color=fff" class="avatar" alt="Avatar">
                        <div class="meta-info">
                            <strong><?= htmlspecialchars($post['username']) ?></strong>
                            <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <h2><a href="post.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
                    <p><?= htmlspecialchars(substr($post['content'], 0, 120)) ?>...</p>
                    
                    <div style="margin-top: auto;">
                        <a href="post.php?id=<?= $post['id'] ?>" class="btn">Read Entry</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>