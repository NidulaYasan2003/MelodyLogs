<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = $_GET['id'] ?? null;
$title = ''; $content = ''; $isEdit = false;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $post = $stmt->fetch();
    if (!$post) { die("Unauthorized access."); }
    $title = $post['title']; $content = $post['content']; $isEdit = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($isEdit) {
        $stmt = $pdo->prepare("UPDATE blogPost SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO blogPost (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $content]);
    }
    header("Location: index.php"); exit;
}
require_once 'includes/header.php';
?>

<div class="editor-container">
    <h2><?= $isEdit ? 'Edit Vocal Journal' : 'Write New Song Journal' ?></h2>
    <form action="editor.php<?= $isEdit ? '?id='.$id : '' ?>" method="POST">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>
        </div>
        <div class="form-group">
            <label>Content</label>
            <!-- The ID here connects to the script below -->
            <textarea id="rich-editor" name="content" rows="15"><?= htmlspecialchars($content) ?></textarea>
        </div>
        <button type="submit" class="btn"><?= $isEdit ? 'Update Post' : 'Publish Post' ?></button>
    </form>
</div>

<!-- TinyMCE Rich Text Editor CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#rich-editor',
    menubar: false,
    skin: 'oxide-dark',
    content_css: 'dark',
    plugins: 'lists link',
    toolbar: 'h2 h3 | bold italic underline | bullist numlist | blockquote | link',
    setup: function (editor) {
        editor.on('change', function () { editor.save(); });
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>