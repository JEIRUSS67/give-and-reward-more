<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();

if (garm_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!garm_csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (garm_too_many_requests('admin_login', 6, 300)) {
        $error = 'Too many login attempts. Please wait a few minutes and try again.';
    } else {
        $email    = garm_clean_string($_POST['email'] ?? '', 190);
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please enter both your email and password.';
        } elseif (garm_attempt_login($email, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            // Deliberately generic message: never reveal whether the
            // email exists in the system.
            $error = 'Incorrect email or password.';
        }
    }
}

$csrfToken = garm_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Give and Reward More</title>
  <meta name="robots" content="noindex, nofollow">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <div class="admin-login-wrap">
    <div class="admin-login-card">
      <p class="nav__brand" style="margin-bottom: var(--space-3);">Give and Reward <span>More</span></p>
      <h1 style="font-size: var(--step-2); margin-bottom: 0.3em;">Admin Sign In</h1>
      <p style="color: var(--color-charcoal-soft); margin-bottom: var(--space-3);">Sign in to manage stories, current needs, and supporter messages.</p>

      <?php if ($error !== ''): ?>
        <div class="form-status form-status--error is-visible" role="alert"><?= garm_escape($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= garm_escape($csrfToken) ?>">
        <div class="form-grid">
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required autocomplete="username">
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
          </div>
        </div>
        <p style="margin-top: var(--space-3);">
          <button type="submit" class="btn btn--primary" style="width:100%; justify-content:center;">Sign In</button>
        </p>
      </form>
    </div>
  </div>
</body>
</html>
