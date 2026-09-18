<?php
/**
 * helpers.php
 *
 * Small shared utilities: input sanitization, JSON responses,
 * CSRF token handling, and basic rate limiting. Kept dependency-free
 * on purpose (no framework), per the project's technology constraints.
 */

require_once __DIR__ . '/config.php';

function garm_json_response(bool $success, string $message, array $extra = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function garm_clean_string(?string $value, int $maxLength = 1000): string
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    if (function_exists('mb_substr')) {
        $value = mb_substr($value, 0, $maxLength);
    } else {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

function garm_is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function garm_is_valid_phone(string $phone): bool
{
    if ($phone === '') {
        return true; // phone is optional on some forms
    }
    return (bool) preg_match('/^[0-9+\-\s()]{6,20}$/', $phone);
}

function garm_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return substr($ip, 0, 45);
}

/**
 * Very small session-based rate limiter to slow down form-spam
 * without requiring an external service. Not a substitute for
 * a proper WAF/captcha on a production deployment.
 */
function garm_too_many_requests(string $bucket, int $maxAttempts = 5, int $windowSeconds = 300): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $key = 'garm_rate_' . $bucket;
    $now = time();
    $attempts = $_SESSION[$key] ?? [];
    $attempts = array_filter($attempts, function ($ts) use ($now, $windowSeconds) {
        return ($now - $ts) < $windowSeconds;
    });

    if (count($attempts) >= $maxAttempts) {
        $_SESSION[$key] = $attempts;
        return true;
    }

    $attempts[] = $now;
    $_SESSION[$key] = $attempts;
    return false;
}

/** Simple honeypot check: a hidden field that real users never fill in. */
function garm_honeypot_tripped(string $fieldName = 'website'): bool
{
    return !empty($_POST[$fieldName]);
}

// ---------------------------------------------------------
// CSRF token handling (used by the admin dashboard forms)
// ---------------------------------------------------------
function garm_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function garm_csrf_verify(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function garm_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
