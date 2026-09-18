<?php
/**
 * create_admin.php
 *
 * One-time setup script for creating the first admin account.
 * Deliberately CLI-only: it refuses to run if accessed over HTTP,
 * so no admin-creation form is ever exposed on the public site.
 *
 * Usage (from the server, not from a browser):
 *   php php/create_admin.php "Full Name" "email@example.org"
 * You will be prompted for a password, which is hashed with
 * PHP's password_hash() before being stored — never in plain text.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

$fullName = $argv[1] ?? null;
$email    = $argv[2] ?? null;

if (!$fullName || !$email || !garm_is_valid_email($email)) {
    fwrite(STDERR, "Usage: php create_admin.php \"Full Name\" \"email@example.org\"\n");
    exit(1);
}

fwrite(STDOUT, "Enter a password for this admin account: ");
system('stty -echo');
$password = trim((string) fgets(STDIN));
system('stty echo');
fwrite(STDOUT, "\n");

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters long.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = garm_db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_active, created_at)
         VALUES (:full_name, :email, :password_hash, "admin", 1, NOW())'
    );
    $stmt->execute([
        ':full_name'     => $fullName,
        ':email'         => $email,
        ':password_hash' => $hash,
    ]);
    fwrite(STDOUT, "Admin account created for {$email}.\n");
} catch (PDOException $e) {
    fwrite(STDERR, "Could not create admin account: " . $e->getMessage() . "\n");
    exit(1);
}
