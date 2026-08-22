<?php
/**
 * MelodyLogs - User Registration
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
require_guest();

$errors = [];
$username = '';
$email = '';
$vocalType = 'Vocalist';
$bio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security token (CSRF). Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $vocalType = trim($_POST['vocal_type'] ?? 'Vocalist');
        $bio      = trim($_POST['bio'] ?? '');

        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
        }

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Check if username or email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute(['username' => $username, 'email' => $email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $errors[] = 'That username or email is already registered. Try signing in instead.';
            }
        }

        // Insert new user if no errors
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, vocal_type, bio, created_at)
                VALUES (:username, :email, :password, 'user', :vocal_type, :bio, NOW())
            ");

            $success = $insertStmt->execute([
                'username'   => $username,
                'email'      => $email,
                'password'   => $hashedPassword,
                'vocal_type' => $vocalType,
                'bio'        => $bio
            ]);

            if ($success) {
                $newUserId = (int)$pdo->lastInsertId();

                // Regenerate session and log the user in immediately
                session_regenerate_id(true);
                $_SESSION['user_id']    = $newUserId;
                $_SESSION['username']   = $username;
                $_SESSION['email']      = $email;
                $_SESSION['role']       = 'user';
                $_SESSION['vocal_type'] = $vocalType;

                set_flash('success', "Welcome to MelodyLogs, {$username}! Your vocal journey begins now.");
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to create your account. Please try again.';
            }
        }
    }
}

$pageTitle = "Create Your Vocalist Account";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Registration Card -->
            <div class="form-glass-card">
                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-mic-fill fs-4"></i>
                    </div>
                    <h1 class="h3 text-white fw-bold mb-1">Join MelodyLogs</h1>
                    <p class="text-secondary small">Create your vocalist profile and start publishing your journey</p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444 !important;">
                        <div class="d-flex align-items-center mb-2 text-danger fw-semibold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Please fix the following:
                        </div>
                        <ul class="mb-0 ps-3 small text-white">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form action="register.php" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label text-white small fw-semibold">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" placeholder="e.g. MariaCallas" required autofocus>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-white small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="name@vocalist.com" required>
                        </div>
                    </div>

                    <!-- Vocal Type / Range -->
                    <div class="mb-3">
                        <label for="vocal_type" class="form-label text-white small fw-semibold">Vocal Classification / Role</label>
                        <select class="form-select" id="vocal_type" name="vocal_type">
                            <option value="Soprano" <?= $vocalType === 'Soprano' ? 'selected' : '' ?>>Soprano (High Female Range)</option>
                            <option value="Mezzo-Soprano" <?= $vocalType === 'Mezzo-Soprano' ? 'selected' : '' ?>>Mezzo-Soprano (Middle Female Range)</option>
                            <option value="Contralto" <?= $vocalType === 'Contralto' ? 'selected' : '' ?>>Contralto (Low Female Range)</option>
                            <option value="Countertenor" <?= $vocalType === 'Countertenor' ? 'selected' : '' ?>>Countertenor (High Male Falsetto / Head)</option>
                            <option value="Tenor" <?= $vocalType === 'Tenor' ? 'selected' : '' ?>>Tenor (High Male Range)</option>
                            <option value="Baritone" <?= $vocalType === 'Baritone' ? 'selected' : '' ?>>Baritone (Middle Male Range)</option>
                            <option value="Bass" <?= $vocalType === 'Bass' ? 'selected' : '' ?>>Bass (Deep Male Range)</option>
                            <option value="Vocal Coach" <?= $vocalType === 'Vocal Coach' ? 'selected' : '' ?>>Vocal Coach / Voice Teacher</option>
                            <option value="Singer-Songwriter" <?= $vocalType === 'Singer-Songwriter' ? 'selected' : '' ?>>Singer-Songwriter</option>
                            <option value="Vocalist" <?= $vocalType === 'Vocalist' ? 'selected' : '' ?>>General Vocalist</option>
                        </select>
                    </div>

                    <!-- Bio (Optional) -->
                    <div class="mb-3">
                        <label for="bio" class="form-label text-white small fw-semibold">Short Bio / Singing Style <span class="text-secondary">(Optional)</span></label>
                        <textarea class="form-control" id="bio" name="bio" rows="2" placeholder="Tell other singers about your musical genre, experience, or vocal goals..."><?= e($bio) ?></textarea>
                    </div>

                    <!-- Password Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="password" class="form-label text-white small fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 chars" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="confirm_password" class="form-label text-white small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gradient py-2 rounded-3 fw-bold">
                            <i class="bi bi-person-check-fill me-2"></i> Register Account
                        </button>
                    </div>

                    <?php $googleClientId = env('GOOGLE_CLIENT_ID', ''); ?>
                    <?php if (!empty($googleClientId)): ?>
                    <!-- Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 border-secondary border-opacity-25">
                        <span class="px-3 text-muted small">or sign up with</span>
                        <hr class="flex-grow-1 border-secondary border-opacity-25">
                    </div>

                    <!-- Google Sign-In -->
                    <div id="g_id_onload"
                         data-client_id="<?= e($googleClientId) ?>"
                         data-login_uri="<?= e(env('APP_URL', 'http://localhost:8000')) ?>/google_callback.php"
                         data-auto_prompt="false"
                         data-context="signup"
                         data-ux_mode="redirect">
                    </div>
                    <div class="d-grid">
                        <div class="g_id_signin"
                             data-type="standard"
                             data-size="large"
                             data-theme="outline"
                             data-text="signup_with"
                             data-shape="pill"
                             data-logo_alignment="center"
                             data-width="100%">
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Switch to Login -->
                    <p class="text-center text-secondary small mb-0 mt-3">
                        Already have an account? 
                        <a href="login.php" class="text-primary text-decoration-none fw-semibold">Sign in here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
