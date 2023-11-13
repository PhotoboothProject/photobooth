<?php

use Photobooth\Service\AssetService;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\ThemeUtility;

$assetService = AssetService::getInstance();

include PathUtility::getAbsolutePath('template/components/main.defaults.php');

?>
<!DOCTYPE html>
<html
    data-ui-theme="<?php echo $config['ui']['style'] ?? 'default'; ?>"
    data-ui-button="<?php echo $config['ui']['button'] ?? 'default'; ?>"
>
<head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="msapplication-TileColor" content="<?= $config['colors']['primary'] ?>">
    <meta name="theme-color" content="<?= $config['colors']['primary'] ?>">

    <title><?= $pageTitle ?></title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?=$assetService->getUrl('resources/img/apple-touch-icon.png')?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=$assetService->getUrl('resources/img/favicon-32x32.png')?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=$assetService->getUrl('resources/img/favicon-16x16.png')?>">
    <link rel="manifest" href="<?=$assetService->getUrl('resources/img/site.webmanifest')?>">
    <link rel="mask-icon" href="<?=$assetService->getUrl('resources/img/safari-pinned-tab.svg')?>" color="#5bbad5">

    <!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />

    <link rel="stylesheet" href="<?=$assetService->getUrl('node_modules/normalize.css/normalize.css')?>" />
    <link rel="stylesheet" href="<?=$assetService->getUrl('node_modules/@fortawesome/fontawesome-free/css/all.min.css')?>" />
    <link rel="stylesheet" href="<?=$assetService->getUrl('node_modules/material-icons/iconfont/material-icons.css')?>">
    <link rel="stylesheet" href="<?=$assetService->getUrl('node_modules/material-icons/css/material-icons.css')?>">
    <?= $photoswipe ? '<link rel="stylesheet" href="' . $assetService->getUrl('node_modules/photoswipe/dist/photoswipe.css') . '" />' : ''?>
    <link rel="stylesheet" href="<?=$assetService->getUrl('resources/css/fonts.css')?>" />
    <link rel="stylesheet" href="<?=$assetService->getUrl('resources/css/framework.css')?>" />
    <?= $photoswipe && $config['gallery']['bottom_bar'] ? '<link rel="stylesheet" href="' . $assetService->getUrl('resources/css/photoswipe-bottom.css') . '"/>' : '' ?>
    <?= is_file(PathUtility::getAbsolutePath('private/overrides.css')) ? '<link rel="stylesheet" href="' . $assetService->getUrl('private/overrides.css') . '"/>' : '' ?>
    <?= ThemeUtility::renderCustomUserStyle($config); ?>
    <script src="<?=$assetService->getUrl('node_modules/jquery/dist/jquery.min.js')?>"></script>
</head>
