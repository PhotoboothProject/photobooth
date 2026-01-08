<?php

require_once __DIR__ . '/../lib/boot.php';

use Photobooth\Utility\PathUtility;

// Enforce admin session
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: ' . PathUtility::getPublicPath('login'));
    exit();
}
