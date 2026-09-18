<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/auth.php';

garm_start_admin_session();
garm_logout_admin();

header('Location: login.php');
exit;
