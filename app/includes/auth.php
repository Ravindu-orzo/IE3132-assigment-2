<?php
/**
 * Session bootstrap + authentication/CSRF helpers.
 * Every protected page includes this file FIRST.
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JS can't read the session cookie
        'cookie_samesite' => 'Lax',  // CSRF hardening
        // 'cookie_secure' => true,   // uncomment once the site is served over HTTPS
    ]);
}

/** Redirects to the login page if nobody is logged in. Call at the top of every protected page. */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUsername(): string
{
    return $_SESSION['username'] ?? '';
}

/** Returns the CSRF token for this session, creating one if needed. */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Timing-safe CSRF check. */
function validateCsrfToken(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
