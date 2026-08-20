<?php
declare(strict_types=1);

require_once 'config/db.php';
require_once 'includes/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT blogPost.*, user.username 
    FROM blogPost 
    JOIN user ON blogPost.user_id = user.id 
    WHERE blogPost.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    die("Vocal entry not found.");
}
?>

<article class="post-card">
    <h1><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><small>By <strong><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></strong> | Posted on <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></small></p>
    <hr style="margin: 15px 0;">
    <div style="white-space: pre-line;">
        <?= htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8') ?>
    </div>

    <?php if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$post['user_id']): ?>
        <div style="margin-top: 20px;">
            <a href="editor.php?id=<?= (int)$post['id'] ?>" class="btn">Edit Entry</a>
            <a href="delete.php?id=<?= (int)$post['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this blog entry?')">Delete Entry</a>
        </div>
    <?php endif; ?>
</article>

<?php require_once 'includes/footer.php'; ?>