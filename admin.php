<?php
/**
 * MelodyLogs - Superadmin Dashboard Console
 *  */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Strict Superadmin Route Guard
require_admin();

$currentUserId = current_user_id();
$currentUser = current_user();

// Handle Administrative POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Security verification failed (Invalid CSRF token). Please try again.');
        session_write_close();
        header('Location: admin.php');
        exit;
    }

    // Regenerate CSRF token after each valid POST to prevent replay attacks
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $action = $_POST['action'] ?? '';

    // 1. Action: Change User Role
    if ($action === 'change_role') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole = trim($_POST['new_role'] ?? '');

        // Validate role against allowed values
        $allowedRoles = ['user', 'admin', 'superAdmin'];
        if ($newRole === '' || !in_array($newRole, $allowedRoles, true)) {
            set_flash('danger', 'Invalid role specified. Allowed roles: ' . implode(', ', $allowedRoles));
            session_write_close();
            header('Location: admin.php#tab-users');
            exit;
        }

        // Validate target user ID
        if ($targetUserId <= 0) {
            set_flash('danger', 'Invalid user ID provided.');
            session_write_close();
            header('Location: admin.php#tab-users');
            exit;
        }

        // Prevent self-demotion to avoid lockout
        if ($targetUserId === $currentUserId && $newRole !== 'superAdmin') {
            set_flash('warning', 'Action blocked: You cannot demote your own administrator account.');
            session_write_close();
            header('Location: admin.php#tab-users');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $targetUserId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                set_flash('danger', 'User account not found.');
            } elseif ($targetUser['role'] === $newRole) {
                set_flash('info', "User '{$targetUser['username']}' already has the '{$newRole}' role. No changes made.");
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
                $updateStmt->execute(['role' => $newRole, 'id' => $targetUserId]);

                if ($updateStmt->rowCount() > 0) {
                    $roleLabel = 'Standard User';
                    if ($newRole === 'superAdmin') $roleLabel = 'SuperAdmin';
                    if ($newRole === 'admin') $roleLabel = 'Administrator';
                    set_flash('success', "User '{$targetUser['username']}' role updated to {$roleLabel}.");
                } else {
                    set_flash('warning', 'Role update had no effect. The user may have been modified by another admin.');
                }
            }
        } catch (PDOException $e) {
            error_log('Admin role change error: ' . $e->getMessage());
            set_flash('danger', 'A database error occurred while updating the user role. Please try again.');
        }

        session_write_close();
        header('Location: admin.php#tab-users');
        exit;
    }

    // 2. Action: Delete User Account
    if ($action === 'delete_user') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($targetUserId <= 0) {
            set_flash('danger', 'Invalid user ID provided.');
            session_write_close();
            header('Location: admin.php#tab-users');
            exit;
        }

        if ($targetUserId === $currentUserId) {
            set_flash('warning', 'Action blocked: You cannot delete your own active administrator account.');
            session_write_close();
            header('Location: admin.php#tab-users');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $targetUserId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                set_flash('danger', 'User account not found or already deleted.');
            } else {
                // Use transaction to ensure both post and user deletion succeed atomically
                $pdo->beginTransaction();

                // Explicitly delete user's posts first (backup to ON DELETE CASCADE)
                $delPostsStmt = $pdo->prepare("DELETE FROM posts WHERE user_id = :uid");
                $delPostsStmt->execute(['uid' => $targetUserId]);
                $deletedPostCount = $delPostsStmt->rowCount();

                // Delete the user account
                $delUserStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $delUserStmt->execute(['id' => $targetUserId]);

                $pdo->commit();
                set_flash('success', "User account '{$targetUser['username']}' and {$deletedPostCount} associated melody log(s) permanently deleted.");
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin user deletion error: ' . $e->getMessage());
            set_flash('danger', 'A database error occurred while deleting the user. Please try again.');
        }

        session_write_close();
        header('Location: admin.php#tab-users');
        exit;
    }

    // 3. Action: Delete Blog Post
    if ($action === 'delete_post') {
        $targetPostId = (int)($_POST['post_id'] ?? 0);

        if ($targetPostId <= 0) {
            set_flash('danger', 'Invalid post ID provided.');
            session_write_close();
            header('Location: admin.php#tab-posts');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, title FROM posts WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $targetPostId]);
            $targetPost = $stmt->fetch();

            if (!$targetPost) {
                set_flash('danger', 'Post not found or already removed.');
            } else {
                $delStmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
                $delStmt->execute(['id' => $targetPostId]);
                set_flash('success', "Melody Log '{$targetPost['title']}' permanently deleted by SuperAdmin.");
            }
        } catch (PDOException $e) {
            error_log('Admin post deletion error: ' . $e->getMessage());
            set_flash('danger', 'A database error occurred while deleting the post. Please try again.');
        }

        session_write_close();
        header('Location: admin.php#tab-posts');
        exit;
    }
}

// Fetch Platform Metrics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superAdmin'")->fetchColumn();

// Most popular category
$topCatStmt = $pdo->query("SELECT category, COUNT(*) as count FROM posts GROUP BY category ORDER BY count DESC LIMIT 1");
$topCategory = $topCatStmt->fetch();

// Fetch All Posts with Author Information
$postsStmt = $pdo->query("
    SELECT 
        posts.*,
        users.username,
        users.email as author_email,
        users.vocal_type as author_vocal_type
    FROM posts
    INNER JOIN users ON posts.user_id = users.id
    ORDER BY posts.created_at DESC
");
$allPosts = $postsStmt->fetchAll();

// Fetch All Users with Post Counts
$usersStmt = $pdo->query("
    SELECT 
        users.*,
        COUNT(posts.id) as total_logs
    FROM users
    LEFT JOIN posts ON users.id = posts.user_id
    GROUP BY users.id
    ORDER BY users.created_at DESC
");
$allUsers = $usersStmt->fetchAll();

$categories = get_vocal_categories();
$pageTitle = "SuperAdmin Console";

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    
    <!-- Superadmin Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1 small rounded-pill">
                    <i class="bi bi-shield-lock-fill me-1"></i> Restricted Console
                </span>
                <span class="text-secondary small">•</span>
                <span class="text-secondary small">Logged in as <strong><?= e($currentUser['username']) ?></strong> (SuperAdmin)</span>
            </div>
            <h1 class="h2 text-white fw-bold mb-0">
                Platform <span class="text-gradient">SuperAdmin Dashboard</span>
            </h1>
        </div>

        <div class="d-flex gap-2">
            <a href="editor.php" class="btn btn-gradient btn-sm rounded-pill px-3 py-2">
                <i class="bi bi-plus-circle me-1"></i> New Publication
            </a>
            <a href="index.php" class="btn btn-outline-glass btn-sm rounded-pill px-3 py-2">
                <i class="bi bi-compass me-1"></i> View Feed
            </a>
        </div>
    </div>

    <!-- Overview Metrics Grid -->
    <div class="row g-3 g-lg-4 mb-4">
        <!-- Total Users Metric -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="admin-stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase tracking-wider">Total Vocalists</span>
                    <div class="stat-icon-wrap" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="stat-number text-white"><?= number_format($totalUsers) ?></div>
                <div class="stat-meta text-muted small">
                    <i class="bi bi-shield-check text-primary me-1"></i> <?= $totalAdmins ?> <?= $totalAdmins === 1 ? 'SuperAdmin' : 'SuperAdmins' ?>
                </div>
            </div>
        </div>

        <!-- Total Posts Metric -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="admin-stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase tracking-wider">Published Logs</span>
                    <div class="stat-icon-wrap" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                </div>
                <div class="stat-number text-white"><?= number_format($totalPosts) ?></div>
                <div class="stat-meta text-muted small">
                    <i class="bi bi-activity text-success me-1"></i> Community Posts
                </div>
            </div>
        </div>

        <!-- Top Category Metric -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="admin-stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase tracking-wider">Top Category</span>
                    <div class="stat-icon-wrap" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                </div>
                <div class="stat-number text-white fs-4 text-truncate" title="<?= e($topCategory['category'] ?? 'None Yet') ?>">
                    <?= !empty($topCategory) ? e($topCategory['category']) : 'No Posts' ?>
                </div>
                <div class="stat-meta text-muted small">
                    <?= !empty($topCategory) ? (int)$topCategory['count'] . ' posts published' : 'Awaiting entries' ?>
                </div>
            </div>
        </div>

        <!-- System Health Metric -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="admin-stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase tracking-wider">System State</span>
                    <div class="stat-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                </div>
                <div class="stat-number text-success fs-4">Operational</div>
                <div class="stat-meta text-muted small">
                    PHP <?= phpversion() ?> · MySQL PDO
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Moderation -->
    <ul class="nav nav-pills admin-nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-posts-btn" data-bs-toggle="pill" data-bs-target="#tab-posts" type="button" role="tab" aria-controls="tab-posts" aria-selected="true">
                <i class="bi bi-journal-text me-2"></i> Melody Logs Moderation (<?= count($allPosts) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-users-btn" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab" aria-controls="tab-users" aria-selected="false">
                <i class="bi bi-people me-2"></i> Vocalist Accounts (<?= count($allUsers) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-system-btn" data-bs-toggle="pill" data-bs-target="#tab-system" type="button" role="tab" aria-controls="tab-system" aria-selected="false">
                <i class="bi bi-gear me-2"></i> System & Config
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="adminTabsContent">
        
        <!-- Tab 1: Melody Logs Moderation Table -->
        <div class="tab-pane fade show active" id="tab-posts" role="tabpanel" aria-labelledby="tab-posts-btn">
            <div class="form-glass-card p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-3">
                    <div>
                        <h4 class="text-white fw-bold mb-1">Community Blog Posts</h4>
                        <p class="text-secondary small mb-0">Review, modify, or remove publications across all categories.</p>
                    </div>
                </div>

                <?php if (empty($allPosts)): ?>
                    <div class="text-center py-5">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <h5 class="text-white">No Blog Posts Published Yet</h5>
                        <p class="text-secondary small mb-3">When vocalists submit logs, they will appear here for administrator management.</p>
                        <a href="editor.php" class="btn btn-gradient btn-sm rounded-pill px-4">Create First Post</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 60px;">ID</th>
                                    <th scope="col">Title & Summary</th>
                                    <th scope="col" style="width: 160px;">Category</th>
                                    <th scope="col" style="width: 160px;">Author</th>
                                    <th scope="col" style="width: 130px;">Date</th>
                                    <th scope="col" class="text-end" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allPosts as $p): ?>
                                    <?php $catMeta = $categories[$p['category']] ?? ['icon' => 'bi-soundwave', 'badge' => 'badge-technique']; ?>
                                    <tr>
                                        <td class="text-muted fw-mono">#<?= (int)$p['id'] ?></td>
                                        <td>
                                            <div class="fw-semibold text-white mb-1">
                                                <a href="post.php?id=<?= (int)$p['id'] ?>" class="text-white text-decoration-none hover-primary">
                                                    <?= e($p['title']) ?>
                                                </a>
                                            </div>
                                            <div class="text-secondary small text-truncate" style="max-width: 420px;">
                                                <?= e($p['summary']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= e($catMeta['badge']) ?> px-2 py-1" style="font-size: 0.75rem;">
                                                <i class="bi <?= e($catMeta['icon']) ?> me-1"></i> <?= e($p['category']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="author-avatar" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                                    <?= mb_strtoupper(mb_substr($p['username'], 0, 1, 'UTF-8')) ?>
                                                </div>
                                                <div class="text-truncate">
                                                    <span class="text-white small fw-semibold d-block"><?= e($p['username']) ?></span>
                                                    <span class="text-muted" style="font-size: 0.7rem;"><?= e($p['author_vocal_type']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-secondary small">
                                            <?= format_date($p['created_at']) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="post.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-glass" title="View Post">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="editor.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-glass text-warning" title="Edit Post as Admin">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-glass text-danger" data-bs-toggle="modal" data-bs-target="#adminDeletePostModal<?= (int)$p['id'] ?>" title="Delete Post">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>

                                            <!-- Admin delete post modals moved to bottom -->
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: User / Vocalist Management -->
        <div class="tab-pane fade" id="tab-users" role="tabpanel" aria-labelledby="tab-users-btn">
            <div class="form-glass-card p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-3">
                    <div>
                        <h4 class="text-white fw-bold mb-1">Registered Vocalists & Practitioners</h4>
                        <p class="text-secondary small mb-0">Manage roles, promote team members, or moderate singer accounts.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px;">ID</th>
                                <th scope="col">Vocalist Profile</th>
                                <th scope="col">Email Address</th>
                                <th scope="col">Classification</th>
                                <th scope="col">Role</th>
                                <th scope="col" class="text-center" style="width: 100px;">Logs</th>
                                <th scope="col" style="width: 130px;">Joined</th>
                                <th scope="col" class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                                <?php $isSelf = ((int)$u['id'] === $currentUserId); ?>
                                <tr>
                                    <td class="text-muted fw-mono">#<?= (int)$u['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="author-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                <?= mb_strtoupper(mb_substr($u['username'], 0, 1, 'UTF-8')) ?>
                                            </div>
                                            <div>
                                                <div class="text-white fw-semibold">
                                                    <?= e($u['username']) ?>
                                                    <?php if ($isSelf): ?>
                                                        <span class="badge bg-primary bg-opacity-25 text-primary ms-1" style="font-size: 0.65rem;">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-secondary small">
                                        <code><?= e($u['email']) ?></code>
                                    </td>
                                    <td>
                                        <span class="text-light-emphasis small"><?= e($u['vocal_type']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'superAdmin'): ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1 rounded-pill small">
                                                <i class="bi bi-shield-shaded me-1"></i> SuperAdmin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50 px-2 py-1 rounded-pill small">
                                                <i class="bi bi-person me-1"></i> User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-white">
                                            <?= (int)$u['total_logs'] ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary small">
                                        <?= format_date($u['created_at']) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Role Toggle Button -->
                                            <?php if (!$isSelf): ?>
                                                <button type="button" class="btn btn-outline-glass text-info" data-bs-toggle="modal" data-bs-target="#roleModal<?= (int)$u['id'] ?>" title="Change User Role">
                                                    <i class="bi bi-person-gear"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-glass text-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?= (int)$u['id'] ?>" title="Delete User">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-muted px-2 py-1 small">Current Account</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- User modals moved to bottom -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: System & Configuration Status -->
        <div class="tab-pane fade" id="tab-system" role="tabpanel" aria-labelledby="tab-system-btn">
            <div class="form-glass-card p-4">
                <h4 class="text-white fw-bold mb-1">System Health & Runtime Configuration</h4>
                <p class="text-secondary small mb-4">Core environment specifications and active database connectivity.</p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border border-secondary border-opacity-25" style="background: rgba(255, 255, 255, 0.02);">
                            <h6 class="text-white fw-bold mb-3 small text-uppercase tracking-wider">
                                <i class="bi bi-hdd-network text-primary me-2"></i> Database & Connection
                            </h6>
                            <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-secondary">
                                <li class="d-flex justify-content-between">
                                    <span>Database Driver:</span>
                                    <span class="text-white font-monospace">MySQL (PDO)</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Database Name:</span>
                                    <span class="text-white font-monospace"><?= e(env('DB_NAME', 'melodylogs_db')) ?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Host & Port:</span>
                                    <span class="text-white font-monospace"><?= e(env('DB_HOST', '127.0.0.1')) ?>:<?= e(env('DB_PORT', '3306')) ?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Charset:</span>
                                    <span class="text-white font-monospace"><?= e(env('DB_CHARSET', 'utf8mb4')) ?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Prepared Statements:</span>
                                    <span class="badge bg-success bg-opacity-25 text-success">Native Prepared (Emulation Disabled)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border border-secondary border-opacity-25" style="background: rgba(255, 255, 255, 0.02);">
                            <h6 class="text-white fw-bold mb-3 small text-uppercase tracking-wider">
                                <i class="bi bi-shield-lock text-success me-2"></i> Security & Session Controls
                            </h6>
                            <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-secondary">
                                <li class="d-flex justify-content-between">
                                    <span>CSRF Protection:</span>
                                    <span class="badge bg-success bg-opacity-25 text-success">Active & Verified</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Session Protection:</span>
                                    <span class="text-white font-monospace">HttpOnly / regenerate_id</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Password Hashing:</span>
                                    <span class="text-white font-monospace">BCrypt (PASSWORD_DEFAULT)</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Environment Mode:</span>
                                    <span class="badge bg-warning bg-opacity-25 text-warning"><?= e(env('APP_ENV', 'development')) ?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Server Time:</span>
                                    <span class="text-white font-monospace"><?= date('Y-m-d H:i:s T') ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<!-- 
     MODALS (Rendered outside table-responsive)
      -->
<!-- Post Modals -->
<?php foreach ($allPosts as $p): ?>
<!-- Modal for deleting post -->
                                            <div class="modal fade text-start" id="adminDeletePostModal<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header border-secondary border-opacity-25">
                                                            <h5 class="modal-title text-danger">
                                                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Delete Blog Post
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-secondary">
                                                            <p class="mb-2">Are you sure you want to delete <strong>"<?= e($p['title']) ?>"</strong> published by <strong><?= e($p['username']) ?></strong>?</p>
                                                            <p class="small text-danger mb-0">As a SuperAdmin, this will permanently purge the entry from the platform.</p>
                                                        </div>
                                                        <div class="modal-footer border-secondary border-opacity-25">
                                                            <button type="button" class="btn btn-outline-glass btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="admin.php" method="POST" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="delete_post">
                                                                <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                                                    <i class="bi bi-trash3 me-1"></i> Delete Post
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
<?php endforeach; ?>

<!-- User Modals -->
<?php foreach ($allUsers as $u): ?>
    <?php $isSelf = ((int)$u['id'] === $currentUserId); ?>
<!-- Change Role Modal -->
                                        <?php if (!$isSelf): ?>
                                            <div class="modal fade text-start" id="roleModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="admin.php" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="change_role">
                                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                            
                                                            <div class="modal-header border-secondary border-opacity-25">
                                                                <h5 class="modal-title text-white">
                                                                    <i class="bi bi-person-gear text-primary me-2"></i> Change Role for <?= e($u['username']) ?>
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-secondary">
                                                                <p class="mb-3">Select the access role for <strong><?= e($u['username']) ?></strong> (<code><?= e($u['email']) ?></code>):</p>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="newRole<?= (int)$u['id'] ?>" class="form-label text-white small fw-bold">Role Assignment</label>
                                                                    <select class="form-select bg-dark text-white border-secondary border-opacity-25" id="newRole<?= (int)$u['id'] ?>" name="new_role">
                                                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Standard Vocalist (User)</option>
                                                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrator (Admin)</option>
                                                                        <option value="superAdmin" <?= $u['role'] === 'superAdmin' ? 'selected' : '' ?>>Superadmin Console Access</option>
                                                                    </select>
                                                                    <div class="form-text text-secondary small mt-2">SuperAdmins have full administrative control over all publications, users, and platform settings.</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-secondary border-opacity-25">
                                                                <button type="button" class="btn btn-outline-glass btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-gradient btn-sm rounded-pill px-3">Update Role</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete User Modal -->
                                            <div class="modal fade text-start" id="deleteUserModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header border-secondary border-opacity-25">
                                                            <h5 class="modal-title text-danger">
                                                                <i class="bi bi-person-x-fill me-2"></i> Delete User Account
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-secondary">
                                                            <p class="mb-2">Are you sure you want to permanently delete the account of <strong>"<?= e($u['username']) ?>"</strong>?</p>
                                                            <p class="small text-danger mb-0">This will remove the user account and automatically delete all <?= (int)$u['total_logs'] ?> publication(s) authored by them.</p>
                                                        </div>
                                                        <div class="modal-footer border-secondary border-opacity-25">
                                                            <button type="button" class="btn btn-outline-glass btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="admin.php" method="POST" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="delete_user">
                                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                                                    <i class="bi bi-trash3 me-1"></i> Delete User & Posts
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
<?php endforeach; ?>

<script>
// Keep active tab on reload or modal redirect
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash) {
        const triggerEl = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
