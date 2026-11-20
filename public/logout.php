<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/auth.php';
logout_user();
header('Location: index.php');
exit;
