<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();

header('Location: ' . (garm_is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
