<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    set_flash('danger', 'Invalid post identifier.');
    header('Location: index.php');
    exit;
}

// Fetch post with author information
$stmt = $pdo->prepare("
    SELECT 
        posts.*,
        users.username,
        users.vocal_type,
        users.bio
    FROM posts
    INNER JOIN users ON posts.user_id = users.id
    WHERE posts.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('warning', 'The requested melody log could not be found or has been removed.');
    header('Location: index.php');
    exit;
}

// Check if current user is the author
$isAuthor = is_logged_in() && (current_user_id() === (int)$post['user_id']);

$categories = get_vocal_categories();
$catMeta = $categories[$post['category']] ?? ['icon' => 'bi-soundwave', 'badge' => 'badge-technique'];
$readTime = reading_time($post['content']);
$pageTitle = $post['title'];

require_once __DIR__ . '/includes/header.php';
?>

<article class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <!-- Breadcrumbs / Top Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="index.php" class="btn btn-outline-glass btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Explore
                </a>

                <!-- Strict Author Controls (Visible ONLY to the Author) -->
                <?php if ($isAuthor): ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="editor.php?id=<?= (int)$post['id'] ?>" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                            <i class="bi bi-pencil me-1"></i> Edit Log
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deletePostModal">
                            <i class="bi bi-trash3 me-1"></i> Delete
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Article Header -->
            <header class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill <?= e($catMeta['badge']) ?> px-3 py-2 fs-6">
                        <i class="bi <?= e($catMeta['icon']) ?> me-1"></i> <?= e($post['category']) ?>
                    </span>
                    <span class="text-secondary small">•</span>
                    <span class="text-secondary small"><i class="bi bi-clock me-1"></i> <?= $readTime ?></span>
                    <span class="text-secondary small">•</span>
                    <span class="text-secondary small"><i class="bi bi-calendar3 me-1"></i> <?= format_date($post['created_at']) ?></span>
                </div>

                <h1 class="display-5 text-white fw-bold mb-3"><?= e($post['title']) ?></h1>

                <p class="lead text-secondary-light mb-4 fs-5" style="border-left: 3px solid var(--accent-purple); padding-left: 1rem;">
                    <?= e($post['summary']) ?>
                </p>

                <!-- Author Micro Bar -->
                <div class="d-flex align-items-center gap-3 py-3 border-top border-bottom border-secondary border-opacity-25">
                    <div class="author-avatar" style="width: 44px; height: 44px; font-size: 1.1rem;">
                        <?= mb_strtoupper(mb_substr($post['username'], 0, 1, 'UTF-8')) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="index.php?user=<?= (int)$post['user_id'] ?>" class="text-white text-decoration-none fw-bold">
                                <?= e($post['username']) ?>
                            </a>
                            <span class="badge bg-dark border border-secondary border-opacity-25 text-secondary" style="font-size: 0.7rem;">
                                <?= e($post['vocal_type']) ?>
                            </span>
                        </div>
                        <small class="text-muted">Published on <?= format_date($post['created_at']) ?></small>
                    </div>
                </div>
            </header>

            <!-- Featured Cover Image (if available) -->
            <?php if (!empty($post['cover_image_url'])): ?>
                <div class="mb-4">
                    <img src="<?= e($post['cover_image_url']) ?>" alt="<?= e($post['title']) ?>" class="post-hero-img shadow-lg" loading="eager" onerror="this.style.display='none'">
                </div>
            <?php endif; ?>

            <!-- Main Post Content -->
            <div class="post-content-body mb-5">
                <?= format_article_content($post['content']) ?>
            </div>

            <!-- Author Biography Card -->
            <div class="author-bio-box mb-5">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                    <div class="author-avatar flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.4rem;">
                        <?= mb_strtoupper(mb_substr($post['username'], 0, 1, 'UTF-8')) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="text-white mb-0"><?= e($post['username']) ?></h5>
                            <span class="badge bg-secondary bg-opacity-25 text-white small"><?= e($post['vocal_type']) ?></span>
                        </div>
                        <p class="text-secondary small mb-2">
                            <?= !empty($post['bio']) ? e($post['bio']) : 'Contributing vocalist and music enthusiast sharing vocal craft on MelodyLogs.' ?>
                        </p>
                        <a href="index.php?user=<?= (int)$post['user_id'] ?>" class="text-primary text-decoration-none small fw-semibold">
                            <i class="bi bi-collection-play me-1"></i> View all logs by <?= e($post['username']) ?> &rarr;
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</article>

<!-- Delete Confirmation Modal (Owner Only) -->
<?php if ($isAuthor): ?>
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title text-danger" id="deletePostModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Delete Melody Log
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-secondary">
                    <p class="mb-2">Are you sure you want to permanently delete <strong>"<?= e($post['title']) ?>"</strong>?</p>
                    <p class="small text-danger mb-0">This action is irreversible and will remove the log from the MelodyLogs community feed.</p>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-glass btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete.php" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">
                            <i class="bi bi-trash3 me-1"></i> Yes, Delete Post
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
