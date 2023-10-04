<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Chroma-Preview Test';
$mainStyle = $config['ui']['style'] . '_style.css';
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
        <div class="buttonbar buttonbar--bottom">
            <?= ComponentUtility::renderButton('startPreview', 'fa fa-play', 'startPreview') ?>
            <?= ComponentUtility::renderButton('stopPreview', 'fa fa-stop', 'stopPreview') ?>

            <label for="chromaImage">Chroma Image:</label>
            <input id="chromaImage" type="text" value="/var/www/html/resources/img/bg.jpg"/>
            <label for="chromaColor">Color:</label>
            <input id="chromaColor" class="settinginput color" type="color" value="#00ff00"/>'
            <label for="chromaSensitivity">Sensitivity:</label>
            <input id="chromaSensitivity" type="number" value="0.4"/>
            <label for="chromaBlend">Blend:</label>
            <input id="chromaBlend" type="number" value="0.1"/>

            <?= ComponentUtility::renderButton('set', 'fa fa-save', 'setChroma') ?>
            <?= ComponentUtility::renderButtonLink('back', 'fa fa-chevron-left', PathUtility::getPublicPath('test')) ?>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_chroma.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
