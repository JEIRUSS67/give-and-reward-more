<?php
/**
 * support.php
 *
 * Handles submissions from the "How would you like to help?"
 * support-request workflow on ways-to-help.html.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    garm_json_response(false, 'Invalid request method.', [], 405);
}

if (garm_honeypot_tripped('website')) {
    garm_json_response(true, 'Thank you. Your support request has been sent.');
}

if (garm_too_many_requests('support', 5, 300)) {
    garm_json_response(false, 'Too many submissions. Please try again in a few minutes.', [], 429);
}

$allowedSupportTypes = [
    'Financial Support', 'Food', 'Clothing', 'Education',
    'Medical Support', 'Volunteering', 'Partnership', 'Other',
];

$fullName    = garm_clean_string($_POST['full_name'] ?? '', 150);
$email       = garm_clean_string($_POST['email'] ?? '', 190);
$phone       = garm_clean_string($_POST['phone'] ?? '', 40);
$country     = garm_clean_string($_POST['country'] ?? '', 100);
$supportType = garm_clean_string($_POST['support_type'] ?? '', 100);
$message     = garm_clean_string($_POST['message'] ?? '', 4000);

$errors = [];
if ($fullName === '') { $errors[] = 'Full name is required.'; }
if ($email === '' || !garm_is_valid_email($email)) { $errors[] = 'A valid email address is required.'; }
if (!garm_is_valid_phone($phone)) { $errors[] = 'Please enter a valid phone number.'; }
if ($country === '') { $errors[] = 'Country is required.'; }
if (!in_array($supportType, $allowedSupportTypes, true)) { $errors[] = 'Please select a valid support type.'; }
if ($message === '') { $errors[] = 'Message is required.'; }

if (!empty($errors)) {
    garm_json_response(false, implode(' ', $errors), [], 422);
}

try {
    $stmt = garm_db()->prepare(
        'INSERT INTO support_requests (full_name, email, phone, country, support_type, message, ip_address, created_at)
         VALUES (:full_name, :email, :phone, :country, :support_type, :message, :ip_address, NOW())'
    );
    $stmt->execute([
        ':full_name'    => $fullName,
        ':email'        => $email,
        ':phone'        => $phone !== '' ? $phone : null,
        ':country'      => $country,
        ':support_type' => $supportType,
        ':message'      => $message,
        ':ip_address'   => garm_client_ip(),
    ]);
} catch (PDOException $e) {
    error_log('[GARM] support.php insert failed: ' . $e->getMessage());
    garm_json_response(false, 'We could not send your request right now. Please try again shortly.', [], 500);
}

garm_json_response(true, 'Thank you. Your support request has been received and our team will follow up with you soon.');
