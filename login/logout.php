<?php

use Photobooth\Utility\PathUtility;

require_once '../lib/boot.php';

session_destroy();
unset($_SESSION['auth']);
unset($_SESSION['rental']);

header('location: ' . PathUtility::getPublicPath());
exit;
