<?php

require_once '../lib/boot.php';

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_index'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['index']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Chroma capture';
$mainStyle = $config['ui']['style'] . '_chromacapture.css';
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body class="gallery-mode--overlay ">
<video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>"
       autoplay playsinline></video>
<div class="chromawrapper">
    <div class="rotarygroup" id="start">
        <div class="top-bar">
<?php
    if (!$config['chromaCapture']['enabled']) {
        echo ComponentUtility::renderButtonLink('close', $config['icons']['close'], PathUtility::getPublicPath());
    }
    if ($config['gallery']['enabled']) {
        echo ComponentUtility::renderButton('gallery', $config['icons']['gallery'], 'gallery-button');
    }
?>
        </div>

        <div class="canvasWrapper initial">
            <canvas id="mainCanvas"></canvas>
        </div>

        <div class="chromaNote">
            <?=$languageService->translate('chromaInfoBefore')?>
        </div>

        <?php include PathUtility::getAbsolutePath('template/components/start.loader.php'); ?>

        <!-- Result Page -->
        <div class="stage" data-stage="result"></div>

        <div class="backgrounds">
        <?php
        $backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($config['keying']['background_path']));
foreach ($backgroundImages as $backgroundImage) {
    echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="backgroundPreview rotaryfocus" onclick="setBackgroundImage(this.src)">';
}
?>
        </div>

        <div class="chroma-control-bar">
<?php
echo ComponentUtility::renderButton('takePhoto', $config['icons']['take_picture'], 'takeChroma');

if ($config['picture']['allow_delete']) {
    echo ComponentUtility::renderButton('delete', $config['icons']['delete'], 'deletebtn');
}

echo ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'reloadPage');
?>
        </div>
    </div>
    <div class="rotarygroup">
        <div id="wrapper">
            <?php include PathUtility::getAbsolutePath('template/components/gallery.php'); ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    onCaptureChromaView = true;
</script>

<?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
<?php include PathUtility::getAbsolutePath('template/components/chroma.footer.php'); ?>

<script src="<?=$assetService->getUrl('resources/js/preview.js')?>"></script>
<script src="<?=$assetService->getUrl('resources/js/core.js')?>"></script>

<?php require_once PathUtility::getAbsolutePath('lib/services_start.php'); ?>
</body>
</html>
