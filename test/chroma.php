<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Chroma-Preview Test';
$mainStyle = 'test_preview.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body>
    <video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" autoplay playsinline></video>

    <div id="wrapper">
        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>
        <div id="no_preview">
            <?=$languageService->translate('no_preview')?>
        </div>
        <div class="buttonbar">
            <a href="#" class="<?php echo $btnClass; ?> startPreview">
                <?=$languageService->translate('startPreview')?>
            </a>
            <a href="#" class="<?php echo $btnClass; ?> stopPreview">
                <?=$languageService->translate('stopPreview')?>
            </a><br>
            <label for="chromaImage">Chroma Image:</label>
            <input id="chromaImage" type="text" value="/var/www/html/resources/img/bg.jpg"/>
            <label for="chromaColor">Color:</label>
            <input id="chromaColor" class="settinginput color noborder" type="color" value="#00ff00"/>'
            <label for="chromaSensitivity">Sensitivity:</label>
            <input id="chromaSensitivity" type="number" value="0.4"/>
            <label for="chromaBlend">Blend:</label>
            <input id="chromaBlend" type="number" value="0.1"/>
            <a href="#" class="<?php echo $btnClass; ?> setChroma">
                <?=$languageService->translate('set')?>
            </a>
            <a href="<?php echo PathUtility::getPublicPath('test'); ?>" class="<?php echo $btnClass; ?> backBtn">
                <?=$languageService->translate('back')?>
            </a>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_chroma.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
