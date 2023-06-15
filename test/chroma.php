<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';

$pageTitle = $config['ui']['branding'] . ' Chroma-Preview Test';
$mainStyle = 'test_preview.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include($fileRoot . 'template/components/main.head.php');
?>

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

    <?php include($fileRoot . 'template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=$fileRoot?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=$fileRoot?>resources/js/test_chroma.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
