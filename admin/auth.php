<?php
/**
 * Admin Authentication Helper
 * Included in all admin pages for session management, CSRF, and auth checks.
 */
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

require_once __DIR__ . '/../config.php';

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh and try again.');
    }
    return true;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function getAdminName() {
    return isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';
}

function calculateReadTime($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($word_count / 200));
    return $minutes;
}

function ensureUploadDirs() {
    $dirs = [
        __DIR__ . '/../uploads/blog/',
        __DIR__ . '/../uploads/blog/content/',
        __DIR__ . '/../uploads/blog/authors/'
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
?>
