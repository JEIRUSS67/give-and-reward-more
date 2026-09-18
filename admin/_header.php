<?php
// Include-only partial. Every admin page must define GARM_ADMIN_PAGE
// and call garm_require_login() before including this file.
if (!defined('GARM_ADMIN_PAGE')) {
    http_response_code(403);
    exit('Direct access is not permitted.');
}
$currentPage = GARM_ADMIN_PAGE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= garm_escape($pageTitle ?? 'Dashboard') ?> | Admin | Give and Reward More</title>
  <meta name="robots" content="noindex, nofollow">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <span class="nav__brand">Give and Reward <span>More</span></span>
      <nav class="admin-nav" aria-label="Admin">
        <a href="dashboard.php" <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>>Overview</a>
        <a href="messages.php" <?= $currentPage === 'messages' ? 'aria-current="page"' : '' ?>>Messages &amp; Requests</a>
        <a href="needs.php" <?= $currentPage === 'needs' ? 'aria-current="page"' : '' ?>>What We Need</a>
        <a href="stories.php" <?= $currentPage === 'stories' ? 'aria-current="page"' : '' ?>>Stories &amp; Updates</a>
      </nav>
      <div style="margin-top:auto;">
        <p style="font-size:0.85em; color: rgba(248,244,236,0.7); margin-bottom:0.6em;">Signed in as<br><strong style="color:#fff;"><?= garm_escape(garm_current_admin_name()) ?></strong></p>
        <a class="btn btn--on-dark btn--sm" href="logout.php">Sign Out</a>
      </div>
    </aside>

    <main class="admin-main">
