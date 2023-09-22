<?php

use Photobooth\Utility\PathUtility;

require_once('../lib/boot.php');

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_manual'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['manual']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

require_once('../lib/configsetup.inc.php');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
    <meta name="theme-color" content="<?=$config['colors']['primary']?>">

    <title><?=$config['ui']['branding']?> FAQ</title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?=PathUtility::getPublicPath()?>resources/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=PathUtility::getPublicPath()?>resources/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=PathUtility::getPublicPath()?>resources/img/favicon-16x16.png">
    <link rel="manifest" href="<?=PathUtility::getPublicPath()?>resources/img/site.webmanifest">
    <link rel="mask-icon" href="<?=PathUtility::getPublicPath()?>resources/img/safari-pinned-tab.svg" color="#5bbad5">

    <!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <link rel="stylesheet" type="text/css" href="../node_modules/github-markdown-css/github-markdown.css">
</head>
<body class="markdown-body" style="padding: 5rem;">

<?php
    if(file_exists(PathUtility::getAbsolutePath('faq/faq.md.php'))) {
        include PathUtility::getAbsolutePath('faq/faq.md.php');
    } else {
        echo '<p>The file <code>faq/faq.md.php</code> does not exist. Please run <code>npm run build:faq</code> to generate it.</p>';
    }
?>

  </body>
</html>
