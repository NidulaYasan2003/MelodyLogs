<?php
/**
 * MelodyLogs - Logout Handler
 */
require_once __DIR__ . '/includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start clean new session for the flash notification
session_start();
set_flash('info', 'You have been safely signed out. Keep practicing your scales!');

header('Location: login.php');
exit;
