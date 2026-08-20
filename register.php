<?php
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            header("Location: login.php?registered=1");
            exit;
        } catch (PDOException $e) {
            $error = 'Username or Email already exists.';
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-form">
    <h2>Artist Registration</h2>
    <?php if ($error): ?><p class="alert alert-danger"><?= $error ?></p><?php endif; ?>
    <form action="register.php" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>