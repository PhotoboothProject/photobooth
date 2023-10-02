<?php

use Photobooth\Utility\PathUtility;

include PathUtility::getAbsolutePath('template/components/main.defaults.php');

?>
<!DOCTYPE html>
<html
    data-ui-theme="<?php echo $config['ui']['style'] ?? 'default'; ?>"
    data-ui-button="<?php echo $config['ui']['button'] ?? 'default'; ?>"
>
<head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?= $config['colors']['primary'] ?>">
    <meta name="theme-color" content="<?= $config['colors']['primary'] ?>">

    <title><?= $pageTitle ?></title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?=PathUtility::getPublicPath()?>resources/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=PathUtility::getPublicPath()?>resources/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=PathUtility::getPublicPath()?>resources/img/favicon-16x16.png">
    <link rel="manifest" href="<?=PathUtility::getPublicPath()?>resources/img/site.webmanifest">
    <link rel="mask-icon" href="<?=PathUtility::getPublicPath()?>resources/img/safari-pinned-tab.svg" color="#5bbad5">

    <!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />

    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>node_modules/normalize.css/normalize.css" />
    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>node_modules/@fortawesome/fontawesome-free/css/all.min.css" />
    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>node_modules/material-icons/iconfont/material-icons.css">
    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>node_modules/material-icons/css/material-icons.css">
    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>resources/css/tailwind.css?v=<?= $config['photobooth']['version'] ?>"/>
    <link rel="stylesheet" href="<?=PathUtility::getPublicPath()?>resources/css/framework.css" />

    <?php
    echo '<link rel="stylesheet" href="' . PathUtility::getPublicPath() . 'resources/css/' . $mainStyle . '?v=' . $config['photobooth']['version'] . '"/>';
if ($photoswipe) {
    echo '<link rel="stylesheet" href="' . PathUtility::getPublicPath() . 'node_modules/photoswipe/dist/photoswipe.css"/>' . "\n";
    if ($config['gallery']['bottom_bar']) {
        echo '<link rel="stylesheet" href="' . PathUtility::getPublicPath() . 'resources/css/photoswipe-bottom.css?v=' . $config['photobooth']['version'] . '"/>' . "\n";
    }
}
if (is_file(PathUtility::getAbsolutePath('private/overrides.css'))) {
    echo '<link rel="stylesheet" href="' . PathUtility::getPublicPath() . 'private/overrides.css?v=' . $config['photobooth']['version'] . '"/>' . "\n";
}
?>
    <script type="text/javascript" src="<?= PathUtility::getPublicPath() ?>node_modules/jquery/dist/jquery.min.js"></script>
</head>
