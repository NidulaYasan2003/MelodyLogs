<?php
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
require_once 'includes/header.php';
?>

<div class="auth-form">
    <h2>Artist Login</h2>
    <?php if (isset($_GET['registered'])): ?>
        <p class="alert alert-success">Registration successful! Please login.</p>
    <?php endif; ?>
    <?php if ($error): ?><p class="alert alert-danger"><?= $error ?></p><?php endif; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>