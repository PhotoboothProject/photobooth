<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
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
$pageTitle = $config['ui']['branding'] . ' Chroma capture';
$mainStyle = $config['ui']['style'] . '_chromacapture.css';
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;
$chromaKeying = true;
$GALLERY_FOOTER = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
$btnClass = 'btn btn--' . $config['ui']['button'] . ' chromaCapture-btn';
?>
<body>
<video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>"
       autoplay playsinline></video>
<div class="chromawrapper">
    <div class="rotarygroup" id="start">
        <div class="top-bar">
            <?php if (!$config['chromaCapture']['enabled']): ?>
                <a href="<?=PathUtility::getPublicPath()?>index.php" class="<?php echo $btnClass; ?> chromaCapture-close-btn rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
            <?php endif; ?>

            <?php if ($config['gallery']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass ?> chromaCapture-gallery-btn rotaryfocus">
                    <i class="<?php echo $config['icons']['gallery']; ?>"></i>
                    <?=$languageService->translate('gallery')?>
                </a>
            <?php endif; ?>
        </div>

        <div class="canvasWrapper <?php echo $uiShape; ?> noborder initial">
            <canvas class="<?php echo $uiShape; ?> noborder" id="mainCanvas"></canvas>
        </div>

        <div class="chromaNote <?php echo $uiShape ?>">
            <?=$languageService->translate('chromaInfoBefore')?>
        </div>

        <?php include PathUtility::getAbsolutePath('template/components/start.loader.php'); ?>

        <!-- Result Page -->
        <div class="stages" id="result"></div>

        <div class="backgrounds <?php echo $uiShape ?>">
        <?php
        $backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($config['keying']['background_path']));
foreach ($backgroundImages as $backgroundImage) {
    echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="backgroundPreview ' . $uiShape . ' rotaryfocus" onclick="setBackgroundImage(this.src)">';
}
?>
        </div>

        <div class="chroma-control-bar">
            <a href="#" class="<?php echo $btnClass; ?> takeChroma chromaCapture rotaryfocus">
                <i class="<?php echo $config['icons']['take_picture']; ?>"></i>
                <?=$languageService->translate('takePhoto')?>
            </a>
            <?php if ($config['picture']['allow_delete']): ?>
                <a href="#" class="<?php echo $btnClass; ?> deletebtn chromaCapture">
                    <i class="<?php echo $config['icons']['delete']; ?>"></i>
                    <?=$languageService->translate('delete')?>
                </a>
            <?php endif; ?>
            <a href="#" class="<?php echo $btnClass; ?> reloadPage chromaCapture rotaryfocus">
                <i class="<?php echo $config['icons']['refresh']; ?>"></i>
                <?=$languageService->translate('reload')?>
            </a>
        </div>
    </div>
    <div class="rotarygroup">
        <div id="wrapper">
            <?php include PathUtility::getAbsolutePath('template/gallery.template.php'); ?>
        </div>

        <?php include PathUtility::getAbsolutePath('template/send-mail.template.php'); ?>
    </div>
</div>
<script type="text/javascript">
    onCaptureChromaView = true;
</script>

<?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

<?php require_once PathUtility::getAbsolutePath('lib/services_start.php'); ?>
</body>
</html>
