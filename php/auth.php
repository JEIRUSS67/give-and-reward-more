<?php
/**
 * auth.php
 *
 * Admin authentication helpers. Passwords are always hashed with
 * PHP's password_hash()/password_verify() — never stored or compared
 * in plain text. Session cookies are hardened before session_start().
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

function garm_start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(ADMIN_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => ADMIN_SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => !GARM_DEBUG, // require HTTPS in production
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Basic idle timeout on top of the cookie lifetime.
    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > ADMIN_SESSION_LIFETIME) {
        garm_logout_admin();
    }
    $_SESSION['last_activity'] = $now;
}

function garm_attempt_login(string $email, string $password): bool
{
    $stmt = garm_db()->prepare(
        'SELECT id, full_name, email, password_hash, role, is_active
         FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Upgrade the hash transparently if PHP's default algorithm/cost changes.
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = password_hash($password, PASSWORD_DEFAULT);
        $update = garm_db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $update->execute([':hash' => $rehash, ':id' => $user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id']   = (int) $user['id'];
    $_SESSION['admin_full_name'] = $user['full_name'];
    $_SESSION['admin_role']      = $user['role'];

    $touch = garm_db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $touch->execute([':id' => $user['id']]);

    return true;
}

function garm_is_logged_in(): bool
{
    return !empty($_SESSION['admin_user_id']);
}

function garm_require_login(): void
{
    if (!garm_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function garm_logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function garm_current_admin_name(): string
{
    return $_SESSION['admin_full_name'] ?? 'Administrator';
}
