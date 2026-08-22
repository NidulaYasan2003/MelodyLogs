<?php
/**
 * MelodyLogs - Post Deletion Handler
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Authentication Guard
require_auth();

// Enforce POST method with CSRF protection for safe state-modifying requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('danger', 'Method Not Allowed. Deletion must be submitted via a verified form.');
    header('Location: index.php');
    exit;
}

if (!verify_csrf()) {
    set_flash('danger', 'Security verification failed (Invalid CSRF Token).');
    header('Location: index.php');
    exit;
}

$postId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$currentUserId = current_user_id();

if ($postId <= 0) {
    set_flash('danger', 'Invalid post ID provided.');
    header('Location: index.php');
    exit;
}

// 1. Verify existence and ownership
$stmt = $pdo->prepare("SELECT id, user_id, title FROM posts WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('warning', 'The post you attempted to delete does not exist.');
    header('Location: index.php');
    exit;
}

// 2. Strict Authorization / Admin Check
if ((int)$post['user_id'] !== $currentUserId && !is_admin()) {
    set_flash('danger', 'Access Denied: You do not possess permission to delete this melody log.');
    header('Location: index.php');
    exit;
}

// 3. Execute Delete Query
if (is_admin()) {
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = :id LIMIT 1");
    $success = $deleteStmt->execute(['id' => $postId]);
} else {
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id LIMIT 1");
    $success = $deleteStmt->execute([
        'id'      => $postId,
        'user_id' => $currentUserId
    ]);
}

if ($success && $deleteStmt->rowCount() > 0) {
    set_flash('success', 'The Melody Log "' . $post['title'] . '" has been permanently deleted.');
} else {
    set_flash('danger', 'Failed to delete the log. Please try again.');
}

$redirectUrl = (isset($_POST['redirect']) && $_POST['redirect'] === 'admin' && is_admin()) ? 'admin.php' : 'index.php';
header("Location: {$redirectUrl}");
exit;
