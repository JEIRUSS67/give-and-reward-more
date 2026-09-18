<?php
/**
 * config.php
 *
 * Central configuration. Reads credentials from environment variables
 * so real database/session secrets are never committed to source
 * control. Fill these in on the server via your hosting panel,
 * a .env loader, or Apache/Nginx environment directives — do not
 * hardcode real values here.
 */

// Prevent direct access to this file being useful on its own.
if (!defined('GARM_APP')) {
    define('GARM_APP', true);
}

// ---- Environment -------------------------------------------------
define('GARM_ENV', getenv('GARM_ENV') ?: 'production');
define('GARM_DEBUG', GARM_ENV === 'development');

// ---- Database ------------------------------------------------------
define('DB_HOST', getenv('GARM_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('GARM_DB_NAME') ?: 'give_and_reward_more');
define('DB_USER', getenv('GARM_DB_USER') ?: '');
define('DB_PASS', getenv('GARM_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---- Site ------------------------------------------------------
define('SITE_NAME', 'Give and Reward More');
define('SITE_URL', getenv('GARM_SITE_URL') ?: 'https://example.org');
define('ADMIN_SESSION_NAME', 'garm_admin_session');
define('ADMIN_SESSION_LIFETIME', 60 * 60 * 2); // 2 hours

// ---- Error handling ------------------------------------------------------
if (GARM_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

// Never echo internals to the client. Log instead.
ini_set('log_errors', '1');
