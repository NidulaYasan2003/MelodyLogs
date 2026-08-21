<?php

// Start session if not active
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Enforce secure cookie settings where applicable
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Sanitize and escape string for HTML output (XSS prevention)
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if the user is currently authenticated
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user ID or null
 *
 * @return int|null
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current authenticated user details from session
 *
 * @return array
 */
function current_user(): array {
    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'username'   => $_SESSION['username'] ?? 'Guest',
        'email'      => $_SESSION['email'] ?? '',
        'vocal_type' => $_SESSION['vocal_type'] ?? 'Vocalist'
    ];
}

/**
 * Guard protected routes: redirect to login if unauthenticated
 *
 * @param string $redirectUrl
 * @return void
 */
function require_auth(string $redirectUrl = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Guard guest-only routes: redirect to homepage if already logged in
 *
 * @param string $redirectUrl
 * @return void
 */
function require_guest(string $redirectUrl = 'index.php'): void {
    if (is_logged_in()) {
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Generate or retrieve CSRF token for the current session
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF token input field
 *
 * @return string
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify CSRF token from POST request
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf(?string $token = null): bool {
    $token = $token ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set a flash message for the next request
 *
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the flash message
 *
 * @return array|null
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date for friendly display
 *
 * @param string|null $dateStr
 * @return string
 */
function format_date(?string $dateStr): string {
    if (empty($dateStr)) {
        return '';
    }
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return (string)$dateStr;
    return date('M j, Y', $timestamp);
}

/**
 * Calculate estimated reading time in minutes
 *
 * @param string $text
 * @return string
 */
function reading_time(string $text): string {
    $wordCount = str_word_count(strip_tags($text));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes) . ' min read';
}

/**
 * Get available vocal categories with metadata
 *
 * @return array
 */
function get_vocal_categories(): array {
    return [
        'Vocal Technique'     => ['icon' => 'bi-soundwave', 'color' => '#8b5cf6', 'badge' => 'badge-technique'],
        'Vocal Warmups'       => ['icon' => 'bi-fire', 'color' => '#ec4899', 'badge' => 'badge-warmup'],
        'Voice Care & Health' => ['icon' => 'bi-heart-pulse', 'color' => '#10b981', 'badge' => 'badge-health'],
        'Studio & Recording'  => ['icon' => 'bi-mic-fill', 'color' => '#3b82f6', 'badge' => 'badge-studio'],
        'Live Performance'    => ['icon' => 'bi-speaker-fill', 'color' => '#f59e0b', 'badge' => 'badge-live'],
        'Songwriting'         => ['icon' => 'bi-music-note-beamed', 'color' => '#06b6d4', 'badge' => 'badge-song']
    ];
}

/**
 * Convert markdown-like headers, bold, italics, lists, and linebreaks safely to HTML
 *
 * @param string $content
 * @return string
 */
function format_article_content(string $content): string {
    // Normalize newlines to Unix style
    $content = str_replace(["\r\n", "\r"], "\n", $content);

    // 1. First escape everything for safety
    $safe = e($content);

    // 2. Parse headers (###, ##, #)
    $safe = preg_replace('/^###\s+(.+)$/m', '<h4 class="post-subheading mt-4 mb-2 text-primary-gradient">$1</h4>', $safe);
    $safe = preg_replace('/^##\s+(.+)$/m', '<h3 class="post-heading mt-4 mb-3 text-white">$1</h3>', $safe);
    $safe = preg_replace('/^#\s+(.+)$/m', '<h2 class="post-title-main mt-4 mb-3 text-white">$1</h2>', $safe);

    // 3. Parse bold & italic (**text**, *text*)
    $safe = preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-white">$1</strong>', $safe);
    $safe = preg_replace('/\*([^\*]+)\*/', '<em class="text-light-emphasis">$1</em>', $safe);

    // 4. Parse bullet lists (- or *)
    $safe = preg_replace('/^[\-\*]\s+(.+)$/m', '<li class="mb-2 text-secondary-light">$1</li>', $safe);

    // 5. Convert numbered items (1. 2. etc.)
    $safe = preg_replace('/^\d+\.\s+(.+)$/m', '<li class="mb-2 text-secondary-light">$1</li>', $safe);

    // 6. Wrap contiguous <li> elements in <ul class="post-list my-3 ps-3">
    $safe = preg_replace('/((?:<li class="mb-2 text-secondary-light">.*?<\/li>\s*)+)/s', '<ul class="post-list my-3 ps-3">$1</ul>', $safe);

    // 7. Convert newlines into paragraphs or linebreaks
    $paragraphs = explode("\n\n", $safe);
    $output = '';
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') continue;
        // If it already starts with a block tag, don't wrap in <p>
        if (preg_match('/^<(h[2-4]|ul|ol|div|blockquote)/', $para)) {
            $output .= $para . "\n";
        } else {
            $output .= '<p class="post-paragraph leading-relaxed">' . nl2br($para) . "</p>\n";
        }
    }

    return $output;
}
