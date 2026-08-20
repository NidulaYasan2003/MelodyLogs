<?php
declare(strict_types=1);

require_once 'config/db.php';
require_once 'includes/header.php';

try {
    $stmt = $pdo->query("
        SELECT blogPost.*, user.username 
        FROM blogPost 
        JOIN user ON blogPost.user_id = user.id 
        ORDER BY created_at DESC
    ");
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Post Query Failure: " . $e->getMessage());
    $posts = [];
}
?>

<section class="welcome-section" style="margin-bottom: 20px;">
    <h1>MelodyLogs Platform</h1>
    <p>A structured publishing portal for singers, vocalists, and songwriters.</p>
</section>

<section class="posts-list">
    <h2>Recent Vocal Logs</h2>
    <?php if (empty($posts)): ?>
        <p style="margin-top: 10px;">No entries available yet. Register and share your first vocal log!</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <h3><a href="post.php?id=<?= (int)$post['id'] ?>"><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                <p><small>By <strong><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></strong> on <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></small></p>
                <p style="margin-top: 8px;"><?= htmlspecialchars(substr($post['content'], 0, 140), ENT_QUOTES, 'UTF-8') ?>...</p>
                <a href="post.php?id=<?= (int)$post['id'] ?>" style="display: inline-block; margin-top: 10px; font-size: 0.9rem;">Read Full Entry &rarr;</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>