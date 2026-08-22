<?php
/**
 * MelodyLogs - Google Sign-In Callback
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Only accept POST requests with a credential token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['credential'])) {
    header('Location: login.php');
    exit;
}

$idToken = $_POST['credential'];
$clientId = env('GOOGLE_CLIENT_ID', '');

if (empty($clientId)) {
    set_flash('danger', 'Google Sign-In is not configured. Please contact the administrator.');
    header('Location: login.php');
    exit;
}

// Verify the Google ID token using Google's tokeninfo endpoint
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
$response = @file_get_contents($verifyUrl);

if ($response === false) {
    set_flash('danger', 'Failed to verify Google credentials. Please try again.');
    header('Location: login.php');
    exit;
}

$payload = json_decode($response, true);

// Validate the token audience matches our client ID
if (!$payload || ($payload['aud'] ?? '') !== $clientId) {
    set_flash('danger', 'Invalid Google token. Please try again.');
    header('Location: login.php');
    exit;
}

$googleId   = $payload['sub'];
$email      = $payload['email'] ?? '';
$name       = $payload['name'] ?? '';
$givenName  = $payload['given_name'] ?? $name;

if (empty($email)) {
    set_flash('danger', 'Google account did not provide an email address.');
    header('Location: login.php');
    exit;
}

// Check if user already exists by google_id or email
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :google_id OR email = :email LIMIT 1");
$stmt->execute(['google_id' => $googleId, 'email' => $email]);
$user = $stmt->fetch();

if ($user) {
    // Existing user — update google_id if not set yet
    if (empty($user['google_id'])) {
        $updateStmt = $pdo->prepare("UPDATE users SET google_id = :google_id WHERE id = :id");
        $updateStmt->execute(['google_id' => $googleId, 'id' => (int)$user['id']]);
    }
} else {
    // New user — create account from Google profile
    // Generate a unique username from the Google name
    $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $givenName);
    if (empty($baseUsername) || strlen($baseUsername) < 3) {
        $baseUsername = 'user';
    }
    $username = substr($baseUsername, 0, 25);

    // Ensure username uniqueness
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $checkStmt->execute(['username' => $username]);
    if ($checkStmt->fetch()) {
        $username = $username . '_' . rand(100, 9999);
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO users (username, email, password, google_id, role, vocal_type, bio, created_at)
        VALUES (:username, :email, '', :google_id, 'user', 'Vocalist', :bio, NOW())
    ");
    $insertStmt->execute([
        'username'  => $username,
        'email'     => $email,
        'google_id' => $googleId,
        'bio'       => 'Signed up via Google'
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    // Fetch the newly created user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $newUserId]);
    $user = $stmt->fetch();
}

// Log the user in
session_regenerate_id(true);
$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['email']      = $user['email'];
$_SESSION['role']       = $user['role'] ?? 'user';
$_SESSION['vocal_type'] = $user['vocal_type'];

set_flash('success', "Welcome, {$user['username']}! Signed in with Google.");
header('Location: index.php');
exit;
