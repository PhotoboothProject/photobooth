<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
    <meta name="theme-color" content="<?=$config['colors']['primary']?>">

    <title><?=$pageTitle ?></title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?=$assetService->getUrl('resources/img/apple-touch-icon.png')?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=$assetService->getUrl('resources/img/favicon-32x32.png')?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=$assetService->getUrl('resources/img/favicon-16x16.png')?>">
    <link rel="manifest" href="<?=$assetService->getUrl('resources/img/site.webmanifest')?>">
    <link rel="mask-icon" href="<?=$assetService->getUrl('resources/img/safari-pinned-tab.svg')?>" color="#5bbad5">

    <link rel="stylesheet" href="<?=$assetService->getUrl('node_modules/@fortawesome/fontawesome-free/css/all.min.css')?>" />
    <link rel="stylesheet" href="<?=$assetService->getUrl('resources/css/tailwind.admin.css')?>" />

    <!-- js -->
    <script type="text/javascript" src="<?=$assetService->getUrl('node_modules/jquery/dist/jquery.min.js')?>"></script>
    <style>
        :root {
            --brand-1: <?=$config['colors']['panel'];?>;
            --brand-2: <?=$config['colors']['primary_light'];?>;
        }
    </style>
</head>
<body class="w-full h-screen overflow-hidden fixed">
