<?php

use Photobooth\Utility\PathUtility;

if (!isset($_SESSION)) {
    session_start();
}

session_destroy();
unset($_SESSION['auth']);
unset($_SESSION['rental']);

header('location: ' . PathUtility::getPublicPath());
exit;
