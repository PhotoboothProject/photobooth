<?php

require_once '../lib/boot.php';

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Chroma-Preview Test';
$photoswipe = false;
$remoteBuzzer = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body>
    <?php include PathUtility::getAbsolutePath('template/components/preview.php'); ?>
    <div class="buttonbar buttonbar--top-left">
        <?= ComponentUtility::renderButtonLink('back', 'fa fa-chevron-left', PathUtility::getPublicPath('test')) ?>
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
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script src="<?=$assetService->getUrl('resources/js/preview.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/test-preview.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/test-chroma.js')?>"></script>

</body>
</html>
