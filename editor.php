<?php
/**
 * MelodyLogs - Post Editor (Create & Update)
 * Unified editor for creating new posts and editing existing user posts with strict authorization
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Authentication Guard
require_auth();

$categories = get_vocal_categories();
$errors = [];

$isEditMode = false;
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;

// Initial field values
$title = '';
$category = 'Vocal Technique';
$summary = '';
$content = '';
$coverImageUrl = '';

// If Edit Mode, fetch post and verify ownership
if ($postId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch();

    if (!$post) {
        set_flash('danger', 'The post you are trying to edit does not exist.');
        header('Location: index.php');
        exit;
    }

    // Strict Ownership Authorization Check
    if ((int)$post['user_id'] !== current_user_id()) {
        set_flash('danger', 'Unauthorized: You can only edit your own melody logs.');
        header('Location: index.php');
        exit;
    }

    $isEditMode = true;
    $title = $post['title'];
    $category = $post['category'];
    $summary = $post['summary'];
    $content = $post['content'];
    $coverImageUrl = $post['cover_image_url'] ?? '';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security token (CSRF). Please resubmit the form.';
    } else {
        $title         = trim($_POST['title'] ?? '');
        $category      = trim($_POST['category'] ?? 'Vocal Technique');
        $summary       = trim($_POST['summary'] ?? '');
        $content       = trim($_POST['content'] ?? '');
        $coverImageUrl = trim($_POST['cover_image_url'] ?? '');

        // Validation
        if (empty($title)) {
            $errors[] = 'Post title is required.';
        } elseif (mb_strlen($title) > 255) {
            $errors[] = 'Post title cannot exceed 255 characters.';
        }

        if (!array_key_exists($category, $categories)) {
            $errors[] = 'Please select a valid vocal category.';
        }

        if (empty($summary)) {
            $errors[] = 'A short summary / hook is required.';
        } elseif (mb_strlen($summary) > 350) {
            $errors[] = 'Summary cannot exceed 350 characters.';
        }

        if (empty($content)) {
            $errors[] = 'Post content cannot be empty.';
        }

        if (!empty($coverImageUrl) && !filter_var($coverImageUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please provide a valid URL for the cover image.';
        }

        // Database Execution
        if (empty($errors)) {
            $currentUserId = current_user_id();

            if ($isEditMode) {
                // Update existing post (strict authorization check in WHERE clause)
                $updateStmt = $pdo->prepare("
                    UPDATE posts 
                    SET title = :title,
                        category = :category,
                        summary = :summary,
                        content = :content,
                        cover_image_url = :cover_image_url,
                        updated_at = NOW()
                    WHERE id = :id AND user_id = :user_id
                ");

                $success = $updateStmt->execute([
                    'title'           => $title,
                    'category'        => $category,
                    'summary'         => $summary,
                    'content'         => $content,
                    'cover_image_url' => !empty($coverImageUrl) ? $coverImageUrl : null,
                    'id'              => $postId,
                    'user_id'         => $currentUserId
                ]);

                if ($success) {
                    set_flash('success', 'Your Melody Log has been updated successfully!');
                    header("Location: post.php?id={$postId}");
                    exit;
                } else {
                    $errors[] = 'Failed to update the post. Please try again.';
                }

            } else {
                // Create new post
                $insertStmt = $pdo->prepare("
                    INSERT INTO posts (user_id, title, category, summary, content, cover_image_url, created_at)
                    VALUES (:user_id, :title, :category, :summary, :content, :cover_image_url, NOW())
                ");

                $success = $insertStmt->execute([
                    'user_id'         => $currentUserId,
                    'title'           => $title,
                    'category'        => $category,
                    'summary'         => $summary,
                    'content'         => $content,
                    'cover_image_url' => !empty($coverImageUrl) ? $coverImageUrl : null
                ]);

                if ($success) {
                    $newId = (int)$pdo->lastInsertId();
                    set_flash('success', 'Your Melody Log has been published to the community!');
                    header("Location: post.php?id={$newId}");
                    exit;
                } else {
                    $errors[] = 'Failed to publish the post. Please try again.';
                }
            }
        }
    }
}

$pageTitle = $isEditMode ? "Edit: " . $title : "Write New Melody Log";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            
            <!-- Breadcrumbs -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="<?= $isEditMode ? "post.php?id={$postId}" : "index.php" ?>" class="btn btn-outline-glass btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> <?= $isEditMode ? 'Cancel Edit' : 'Back to Feed' ?>
                </a>
                <span class="badge bg-secondary bg-opacity-25 text-white">
                    <i class="bi <?= $isEditMode ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-1"></i>
                    <?= $isEditMode ? 'Editing Post #' . $postId : 'New Publication' ?>
                </span>
            </div>

            <!-- Editor Form Card -->
            <div class="form-glass-card">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h3 text-white fw-bold mb-1">
                        <?= $isEditMode ? 'Edit Melody Log' : 'Compose a New Melody Log' ?>
                    </h1>
                    <p class="text-secondary small mb-0">
                        Share vocal tips, warmups, acoustic discoveries, or studio logs with the singer community.
                    </p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444 !important;">
                        <div class="d-flex align-items-center mb-1 text-danger fw-semibold small">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i> Please correct the following errors:
                        </div>
                        <ul class="mb-0 ps-3 small text-white">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Post Form -->
                <form action="editor.php<?= $isEditMode ? '?id=' . (int)$postId : '' ?>" method="POST">
                    <?= csrf_field() ?>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label text-white small fw-semibold">Log Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. 5 Daily Exercises for Expanding Upper Register Resonance" required autofocus>
                    </div>

                    <!-- Category & Cover Image Grid -->
                    <div class="row g-3 mb-3">
                        <!-- Category Selector -->
                        <div class="col-md-6">
                            <label for="category" class="form-label text-white small fw-semibold">Vocal Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <?php foreach ($categories as $catName => $meta): ?>
                                    <option value="<?= e($catName) ?>" <?= $category === $catName ? 'selected' : '' ?>>
                                        <?= e($catName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cover Image URL -->
                        <div class="col-md-6">
                            <label for="cover_image_url" class="form-label text-white small fw-semibold">Cover Image URL <span class="text-secondary">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-image"></i></span>
                                <input type="url" class="form-control" id="cover_image_url" name="cover_image_url" value="<?= e($coverImageUrl) ?>" placeholder="https://images.unsplash.com/...">
                            </div>
                        </div>
                    </div>

                    <!-- Preset Image Chips for Quick Demo Selection -->
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1" style="font-size: 0.75rem;">Quick Cover Presets:</small>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-glass btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="document.getElementById('cover_image_url').value='https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=1200&q=80'">
                                🎤 Microphone
                            </button>
                            <button type="button" class="btn btn-outline-glass btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="document.getElementById('cover_image_url').value='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=1200&q=80'">
                                🎵 Stage & Lights
                            </button>
                            <button type="button" class="btn btn-outline-glass btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="document.getElementById('cover_image_url').value='https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?auto=format&fit=crop&w=1200&q=80'">
                                🎛️ Studio Gear
                            </button>
                        </div>
                    </div>

                    <!-- Summary / Excerpt -->
                    <div class="mb-3">
                        <label for="summary" class="form-label text-white small fw-semibold">Short Summary / Hook <span class="text-danger">*</span> (Max 350 chars)</label>
                        <textarea class="form-control" id="summary" name="summary" rows="2" maxlength="350" placeholder="A 1-2 sentence hook summarizing the vocal insight for the feed preview..." required><?= e($summary) ?></textarea>
                    </div>

                    <!-- Content (Markdown / Article Body) -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="content" class="form-label text-white small fw-semibold mb-0">Detailed Content <span class="text-danger">*</span></label>
                            <small class="text-secondary" style="font-size: 0.75rem;">
                                Supports <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>, <code>- bullets</code>
                            </small>
                        </div>
                        <textarea class="form-control font-monospace" id="content" name="content" rows="12" placeholder="Write your full guide, routine, or story here...&#10;&#10;## Warmup Drill&#10;1. Lip trills across octave 3&#10;2. Open vowel resonance on 'Ah'..." required><?= e($content) ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= $isEditMode ? "post.php?id={$postId}" : "index.php" ?>" class="btn btn-outline-glass rounded-pill px-4">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-bold">
                            <i class="bi <?= $isEditMode ? 'bi-check2-circle' : 'bi-send-fill' ?> me-2"></i>
                            <?= $isEditMode ? 'Update Melody Log' : 'Publish to Feed' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
