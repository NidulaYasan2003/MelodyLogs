<?php
/**
 * MelodyLogs - User Login
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already authenticated
require_guest();

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security token (CSRF). Please refresh and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier)) {
            $errors[] = 'Username or Email is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            // Find user by username OR email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute(['username' => $identifier, 'email' => $identifier]);
            $user = $stmt->fetch();

            if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                // Regenerate session ID to protect against session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'] ?? 'user';
                $_SESSION['vocal_type'] = $user['vocal_type'];

                set_flash('success', "Welcome back, {$user['username']}! Great to have you on MelodyLogs.");
                header('Location: index.php');
                exit;
            } elseif ($user && empty($user['password']) && !empty($user['google_id'])) {
                $errors[] = 'This account uses Google Sign-In. Please click "Sign in with Google" below.';
            } else {
                $errors[] = 'Invalid credentials. Please verify your username/email and password.';
            }
        }
    }
}

$pageTitle = "Sign In";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Login Card -->
            <div class="form-glass-card">
                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-soundwave fs-4"></i>
                    </div>
                    <h1 class="h3 text-white fw-bold mb-1">Welcome Back</h1>
                    <p class="text-secondary small">Sign in to your MelodyLogs vocalist profile</p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444 !important;">
                        <div class="d-flex align-items-center mb-1 text-danger fw-semibold small">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i> Authentication Error:
                        </div>
                        <ul class="mb-0 ps-3 small text-white">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="login.php" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Username or Email -->
                    <div class="mb-3">
                        <label for="identifier" class="form-label text-white small fw-semibold">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="identifier" name="identifier" value="<?= e($identifier) ?>" placeholder="Enter username or email" required autofocus>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label text-white small fw-semibold mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gradient py-2 rounded-3 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to MelodyLogs
                        </button>
                    </div>

                    <?php $googleClientId = env('GOOGLE_CLIENT_ID', ''); ?>
                    <?php if (!empty($googleClientId)): ?>
                    <!-- Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 border-secondary border-opacity-25">
                        <span class="px-3 text-muted small">or continue with</span>
                        <hr class="flex-grow-1 border-secondary border-opacity-25">
                    </div>

                    <!-- Google Sign-In -->
                    <div id="g_id_onload"
                         data-client_id="<?= e($googleClientId) ?>"
                         data-login_uri="<?= e(env('APP_URL', 'http://localhost:8000')) ?>/google_callback.php"
                         data-auto_prompt="false"
                         data-context="signin"
                         data-ux_mode="redirect">
                    </div>
                    <div class="d-grid">
                        <div class="g_id_signin"
                             data-type="standard"
                             data-size="large"
                             data-theme="outline"
                             data-text="signin_with"
                             data-shape="pill"
                             data-logo_alignment="center"
                             data-width="100%">
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Switch to Register -->
                    <p class="text-center text-secondary small mb-0 mt-3">
                        Don't have an account yet? 
                        <a href="register.php" class="text-primary text-decoration-none fw-semibold">Join MelodyLogs free</a>
                    </p>
                </form>

                <!-- Demo Credentials Helper Note -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
                    <small class="text-muted d-block mb-1">Default Admin Account (Password: <code>admin123</code>):</small>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <span class="badge bg-dark border border-secondary border-opacity-25 text-secondary">admin@melodylogs.com</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
