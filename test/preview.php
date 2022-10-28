<?php
require_once '../lib/config.php';

$btnClass = 'btn btn--' . $config['ui']['button'];
$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?= $config['colors']['primary'] ?>">
    <meta name="theme-color" content="<?= $config['colors']['primary'] ?>">

    <title><?= $config['ui']['branding'] ?> Preview-Test</title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
    <link rel="manifest" href="../resources/img/site.webmanifest">
    <link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

    <!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>

    <link rel="stylesheet" href="../node_modules/normalize.css/normalize.css"/>
    <link rel="stylesheet" href="../node_modules/font-awesome/css/font-awesome.css"/>
    <link rel="stylesheet" href="../node_modules/material-icons/iconfont/material-icons.css">
    <link rel="stylesheet" href="../resources/css/test_preview.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php if (is_file('../private/overrides.css')): ?>
        <link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
</head>

<body>

    <video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>"
       autoplay playsinline></video>

    <div id="wrapper">
        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

        <div id="no_preview">
            <span data-i18n="no_preview"></span>
        </div>

		<div class="buttonbar">
			<a href="#" class="<?php echo $btnClass; ?> startPreview"><span data-i18n="startPreview"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> stopPreview"><span data-i18n="stopPreview"></span></a>
        </div>
    </div>

<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
