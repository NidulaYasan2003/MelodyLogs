<?php
/**
 * MelodyLogs - Homepage & Feed
 * Displays hero section, category filters, search, and responsive post card grid
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Filter parameters
$selectedCategory = trim($_GET['category'] ?? '');
$searchQuery      = trim($_GET['q'] ?? '');
$filterUserId     = isset($_GET['user']) ? (int)$_GET['user'] : null;

$filterAuthorName = null;

// Build dynamic query with prepared statements
$sql = "
    SELECT 
        posts.*,
        users.username,
        users.vocal_type
    FROM posts
    INNER JOIN users ON posts.user_id = users.id
    WHERE 1=1
";
$params = [];

if (!empty($selectedCategory)) {
    $sql .= " AND posts.category = :category";
    $params['category'] = $selectedCategory;
}

if (!empty($searchQuery)) {
    $sql .= " AND (posts.title LIKE :search1 OR posts.summary LIKE :search2 OR posts.content LIKE :search3)";
    $searchTerm = "%{$searchQuery}%";
    $params['search1'] = $searchTerm;
    $params['search2'] = $searchTerm;
    $params['search3'] = $searchTerm;
}

if (!empty($filterUserId)) {
    $sql .= " AND posts.user_id = :user_id";
    $params['user_id'] = $filterUserId;

    // Fetch author username for display heading
    $authorStmt = $pdo->prepare("SELECT username FROM users WHERE id = :id LIMIT 1");
    $authorStmt->execute(['id' => $filterUserId]);
    $filterAuthorName = $authorStmt->fetchColumn();
}

$sql .= " ORDER BY posts.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = get_vocal_categories();

$pageTitle = !empty($filterAuthorName) 
    ? "Logs by {$filterAuthorName}" 
    : (!empty($selectedCategory) ? "{$selectedCategory} Logs" : "Explore Vocal Logs & Guides");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<section class="hero-wrapper">
    <div class="container position-relative">
        <div class="hero-pill">
            <span class="soundwave-bar" style="height: 10px;"></span>
            <span>The Premier Platform for Singers & Vocalists</span>
            <i class="bi bi-stars text-warning"></i>
        </div>

        <h1 class="hero-title text-white">
            Harmonize Your Voice.<br>
            <span class="text-gradient">Share Your Vocal Journey.</span>
        </h1>

        <p class="hero-subtitle">
            Explore daily warmup routines, passaggio mixing techniques, studio mic shootouts, and vocal health insights written by passionate singers and vocal coaches.
        </p>

        <!-- Search Bar -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-8 col-lg-6">
                <form action="index.php" method="GET" class="d-flex gap-2">
                    <?php if (!empty($selectedCategory)): ?>
                        <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">
                    <?php endif; ?>
                    <div class="input-group shadow-lg">
                        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control" placeholder="Search vocal warmups, mix voice, mics..." value="<?= e($searchQuery) ?>">
                        <button type="submit" class="btn btn-gradient px-4">Find</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="category-filter-bar">
            <a href="index.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" class="cat-pill <?= empty($selectedCategory) && empty($filterUserId) ? 'active' : '' ?>">
                <i class="bi bi-grid-fill me-1"></i> All Topics
            </a>
            <?php foreach ($categories as $name => $meta): ?>
                <a href="index.php?category=<?= urlencode($name) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="cat-pill <?= $selectedCategory === $name ? 'active' : '' ?>">
                    <i class="bi <?= e($meta['icon']) ?> me-1"></i> <?= e($name) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($filterAuthorName)): ?>
            <div class="alert alert-info d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill mb-4" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #93c5fd;">
                <i class="bi bi-person-circle fs-5"></i>
                <span>Showing logs published by <strong><?= e($filterAuthorName) ?></strong></span>
                <a href="index.php" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" title="Clear filter"></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Feed / Card Grid Section -->
<section class="container pb-5">
    <?php if (empty($posts)): ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <div class="brand-icon-box mx-auto mb-4" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="bi bi-music-note-list"></i>
            </div>
            <h3 class="text-white fw-bold mb-2">No Melody Logs Found</h3>
            <p class="text-secondary max-w-md mx-auto mb-4" style="max-width: 480px;">
                <?php if (!empty($searchQuery) || !empty($selectedCategory) || !empty($filterUserId)): ?>
                    No posts matched your criteria. Try resetting filters or searching with different vocal keywords.
                <?php else: ?>
                    The stage is completely open! Be the first vocalist to publish a blog post and share your musical insights with the world.
                <?php endif; ?>
            </p>
            <div class="d-flex justify-content-center gap-3">
                <?php if (!empty($searchQuery) || !empty($selectedCategory) || !empty($filterUserId)): ?>
                    <a href="index.php" class="btn btn-outline-glass rounded-pill px-4">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> View All Logs
                    </a>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                    <a href="editor.php" class="btn btn-gradient rounded-pill px-4">
                        <i class="bi bi-pencil-square me-1"></i> Write the First Log
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-gradient rounded-pill px-4">
                        <i class="bi bi-person-plus me-1"></i> Join & Write
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Posts Grid -->
        <div class="row g-4">
            <?php foreach ($posts as $post): ?>
                <?php
                    $catMeta = $categories[$post['category']] ?? ['icon' => 'bi-soundwave', 'badge' => 'badge-technique'];
                    $readTime = reading_time($post['content']);
                    $coverImg = !empty($post['cover_image_url']) 
                        ? $post['cover_image_url'] 
                        : 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80';
                ?>
                <div class="col-md-6 col-lg-4">
                    <article class="glass-card">
                        <!-- Cover Image & Floating Category Badge -->
                        <div class="card-img-wrap">
                            <img src="<?= e($coverImg) ?>" alt="<?= e($post['title']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80';">
                            <span class="category-badge-floating <?= e($catMeta['badge']) ?>">
                                <i class="bi <?= e($catMeta['icon']) ?> me-1"></i> <?= e($post['category']) ?>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body-custom">
                            <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                                <span><i class="bi bi-clock me-1"></i> <?= $readTime ?></span>
                                <span>•</span>
                                <span><i class="bi bi-calendar3 me-1"></i> <?= format_date($post['created_at']) ?></span>
                            </div>

                            <h2 class="card-title-custom">
                                <a href="post.php?id=<?= (int)$post['id'] ?>">
                                    <?= e($post['title']) ?>
                                </a>
                            </h2>

                            <p class="text-secondary small leading-relaxed mb-4">
                                <?= e(mb_strimwidth($post['summary'], 0, 140, '...')) ?>
                            </p>

                            <!-- Author Info Bar -->
                            <div class="card-author-bar">
                                <div class="author-avatar">
                                    <?= mb_strtoupper(mb_substr($post['username'], 0, 1, 'UTF-8')) ?>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 overflow-hidden">
                                    <a href="index.php?user=<?= (int)$post['user_id'] ?>" class="text-white text-decoration-none fw-semibold small text-truncate hover-primary">
                                        <?= e($post['username']) ?>
                                    </a>
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        <?= e($post['vocal_type']) ?>
                                    </span>
                                </div>
                                <a href="post.php?id=<?= (int)$post['id'] ?>" class="btn btn-outline-glass btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Read Melody Log">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
