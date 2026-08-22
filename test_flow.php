<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function http_request($url, $postData = null) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    
    return ['body' => $response, 'code' => $httpCode, 'redirect' => $redirect];
}

// 1. Get Login Page (Extract CSRF)
$res = http_request('http://localhost:8000/login.php');
if (!preg_match('/name="csrf_token" value="(.*?)"/', $res['body'], $matches)) {
    die("Failed to get CSRF token from login page\n");
}
$csrf = $matches[1];
echo "CSRF Token: $csrf\n";

// 2. Perform Login
$loginData = [
    'csrf_token' => $csrf,
    'identifier' => 'admin@melodylogs.com',
    'password' => 'admin123',
    'login' => '1'
];
$res = http_request('http://localhost:8000/login.php', $loginData);
echo "Login HTTP Code: " . $res['code'] . "\n";
if ($res['redirect']) echo "Login Redirect: " . $res['redirect'] . "\n";

// 3. Access Admin Page
$res = http_request('http://localhost:8000/admin.php');
if (!preg_match_all('/name="csrf_token" value="(.*?)"/', $res['body'], $matches)) {
    die("Failed to get CSRF token from admin page\n");
}
$csrf = $matches[1][0];
echo "Admin CSRF Token: $csrf\n";

// 4. Submit Role Change
$roleChangeData = [
    'csrf_token' => $csrf,
    'action' => 'change_role',
    'user_id' => '2',
    'new_role' => 'user'
];
$res = http_request('http://localhost:8000/admin.php', $roleChangeData);
echo "Role Change HTTP Code: " . $res['code'] . "\n";
if ($res['redirect']) echo "Role Change Redirect: " . $res['redirect'] . "\n";

// 5. Access Admin Page Again to read flash message
$res = http_request('http://localhost:8000/admin.php');
if (preg_match('/<div class="alert alert-(.*?) alert-dismissible.*?>(.*?)<\/div>/s', $res['body'], $matches)) {
    echo "Flash Message: [{$matches[1]}] " . trim(strip_tags($matches[2])) . "\n";
} else {
    echo "No flash message found.\n";
}

?>
