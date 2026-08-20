<?php
declare(strict_types=1);

require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$title   = '';
$content = '';
$isEdit  = false;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $post = $stmt->fetch();

    if (!$post) {
        die("Unauthorized access or entry not found.");
    }
    $title   = $post['title'];
    $content = $post['content'];
    $isEdit  = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!empty($title) && !empty($content)) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE blogPost SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO blogPost (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
        }
        header("Location: index.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="editor-card" style="max-width: 650px; margin: 20px auto;">
    <h2><?= $isEdit ? 'Edit Vocal Entry' : 'Create New Vocal Entry' ?></h2>
    <form action="editor.php<?= $isEdit ? '?id=' . $id : '' ?>" method="POST">
        <div class="form-group">
            <label>Entry Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label>Entry Content</label>
            <textarea name="content" class="form-control" rows="10" required><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="btn"><?= $isEdit ? 'Update Entry' : 'Publish Entry' ?></button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>