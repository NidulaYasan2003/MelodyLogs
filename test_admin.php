<?php
session_start();

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Admin';
$_SESSION['email'] = 'admin@melodylogs.com';
$_SESSION['role'] = 'superadmin';
$_SESSION['vocal_type'] = 'Platform Administrator';

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// Simulate a POST to change user #2's role to 'superadmin'
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PHP_SELF'] = '/admin.php';
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_POST = [
    'csrf_token' => $csrfToken,
    'action' => 'change_role',
    'user_id' => '2',
    'new_role' => 'superadmin'
];

// Override header() and exit to capture
function header_capture($str) {
    echo "HEADER: {$str}\n";
}

// We can't easily override header/exit, so let's trace manually
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "is_admin(): " . (is_admin() ? 'true' : 'false') . "\n";
echo "verify_csrf(): " . (verify_csrf() ? 'true' : 'false') . "\n";
echo "Action: " . ($_POST['action'] ?? 'NONE') . "\n";
echo "Target user_id: " . ($_POST['user_id'] ?? 'NONE') . "\n";
echo "New role: " . ($_POST['new_role'] ?? 'NONE') . "\n\n";

// Now manually execute the role change logic
$currentUserId = current_user_id();
$action = $_POST['action'];
$targetUserId = (int)($_POST['user_id'] ?? 0);
$newRole = trim($_POST['new_role'] ?? 'user');

echo "currentUserId: {$currentUserId}\n";
echo "targetUserId: {$targetUserId}\n";
echo "Is self-demotion: " . (($targetUserId === $currentUserId && $newRole !== 'superadmin') ? 'YES' : 'NO') . "\n\n";

if (!in_array($newRole, ['user', 'superadmin'], true)) {
    echo "VALIDATION FAIL: Invalid role\n";
} else {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $targetUserId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        echo "ERROR: User not found!\n";
    } else {
        echo "Found user: #{$targetUser['id']} {$targetUser['username']}\n";
        
        $updateStmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $result = $updateStmt->execute(['role' => $newRole, 'id' => $targetUserId]);
        echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        echo "Rows affected: " . $updateStmt->rowCount() . "\n";
        
        // Verify
        $verifyStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $verifyStmt->execute(['id' => $targetUserId]);
        echo "Verified role: " . $verifyStmt->fetchColumn() . "\n";
        
        // Reset user back to 'user'
        $pdo->prepare("UPDATE users SET role = :role WHERE id = :id")->execute(['role' => 'user', 'id' => $targetUserId]);
        echo "\n(Reset user #2 back to 'user')\n";
    }
}
