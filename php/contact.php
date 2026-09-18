<?php
/**
 * contact.php
 *
 * Handles submissions from the Contact page's message form.
 * Server-side validation is authoritative; the browser-side checks
 * in js/forms.js are a convenience only and are never trusted here.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    garm_json_response(false, 'Invalid request method.', [], 405);
}

// Honeypot: a hidden field named "website" that only bots fill in.
if (garm_honeypot_tripped('website')) {
    // Pretend success so the bot does not learn its submission was rejected.
    garm_json_response(true, 'Thank you. Your message has been sent.');
}

if (garm_too_many_requests('contact', 5, 300)) {
    garm_json_response(false, 'Too many submissions. Please try again in a few minutes.', [], 429);
}

$fullName = garm_clean_string($_POST['full_name'] ?? '', 150);
$email    = garm_clean_string($_POST['email'] ?? '', 190);
$phone    = garm_clean_string($_POST['phone'] ?? '', 40);
$subject  = garm_clean_string($_POST['subject'] ?? '', 200);
$message  = garm_clean_string($_POST['message'] ?? '', 4000);

$errors = [];
if ($fullName === '') { $errors[] = 'Full name is required.'; }
if ($email === '' || !garm_is_valid_email($email)) { $errors[] = 'A valid email address is required.'; }
if (!garm_is_valid_phone($phone)) { $errors[] = 'Please enter a valid phone number.'; }
if ($subject === '') { $errors[] = 'Subject is required.'; }
if ($message === '') { $errors[] = 'Message is required.'; }

if (!empty($errors)) {
    garm_json_response(false, implode(' ', $errors), [], 422);
}

try {
    $stmt = garm_db()->prepare(
        'INSERT INTO contact_messages (full_name, email, phone, subject, message, ip_address, created_at)
         VALUES (:full_name, :email, :phone, :subject, :message, :ip_address, NOW())'
    );
    $stmt->execute([
        ':full_name'  => $fullName,
        ':email'      => $email,
        ':phone'      => $phone !== '' ? $phone : null,
        ':subject'    => $subject,
        ':message'    => $message,
        ':ip_address' => garm_client_ip(),
    ]);
} catch (PDOException $e) {
    error_log('[GARM] contact.php insert failed: ' . $e->getMessage());
    garm_json_response(false, 'We could not send your message right now. Please try again shortly.', [], 500);
}

garm_json_response(true, 'Thank you. Your message has been received and we will respond as soon as possible.');
