<?php
require_once '../lib/config.php';

$btnClass = 'btn btn--' . $config['ui']['button'];
$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];

$fileRoot = '../';
$pageTitle = $config['ui']['branding'] . ' Chroma-Test';
?>

<!DOCTYPE html>
<html>

<head>
	<?php include('../template/components/mainHead.php'); ?>

    <link rel="stylesheet" href="../resources/css/test_preview.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php if (is_file('../private/overrides.css')): ?>
        <link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
</head>

<body>
    <video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" autoplay playsinline></video>

    <div id="wrapper">
        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

        <div id="no_preview">
            <span data-i18n="no_preview"></span>
        </div>

		<div class="buttonbar">
            <a href="#" class="<?php echo $btnClass; ?> startPreview"><span data-i18n="startPreview"></span></a>
            <a href="#" class="<?php echo $btnClass; ?> stopPreview"><span data-i18n="stopPreview"></span></a><br>
            <label for="chromaImage">Chroma Image:</label>
            <input id="chromaImage" type="text" value="/var/www/html/resources/img/bg.jpg"/>
            <label for="chromaColor">Color:</label>
            <input id="chromaColor" class="settinginput color noborder" type="color" value="#00ff00"/>'
            <label for="chromaSensitivity">Sensitivity:</label>
            <input id="chromaSensitivity" type="number" value="0.4"/>
            <label for="chromaBlend">Blend:</label>
            <input id="chromaBlend" type="number" value="0.1"/>
            <a href="#" class="<?php echo $btnClass; ?> setChroma">Set</a>
        </div>
    </div>

<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/test_chroma.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
