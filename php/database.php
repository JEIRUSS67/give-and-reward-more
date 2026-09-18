<?php
/**
 * database.php
 *
 * Single PDO connection helper. Every query elsewhere in the
 * codebase must use prepared statements through this connection —
 * never interpolate request data directly into SQL.
 */

require_once __DIR__ . '/config.php';

function garm_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('[GARM] Database connection failed: ' . $e->getMessage());
        // Never leak connection details to the client.
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'We are unable to process your request right now. Please try again shortly.',
        ]);
        exit;
    }

    return $pdo;
}
