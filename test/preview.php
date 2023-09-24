<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Preview-Test';
$mainStyle = 'test_preview.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body>
    <?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])): ?>
    <img id="picture--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['picture']['htmlframe']; ?>" alt="pictureFrame" />
    <?php endif; ?>
    <?php if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
    <img id="collage--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['collage']['htmlframe']; ?>" alt="collageFrame" />
    <?php endif; ?>
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
            </a>
            <?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])): ?>
            <a href="#" class="<?php echo $btnClass; ?> showPictureFrame">
                <?=$languageService->translate('showPictureFrame')?>
            </a>
            <?php endif; ?>
            <?php if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
                <a href="#" class="<?php echo $btnClass; ?> showCollageFrame">
                    <?=$languageService->translate('showCollageFrame')?>
                </a>
            <?php endif; ?>
            <?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe']) || $config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
                <a href="#" class="<?php echo $btnClass; ?> hideFrame">
                    <?=$languageService->translate('hideFrame')?>
                </a>
            <?php endif; ?>
            <a href="<?php echo PathUtility::getPublicPath('test'); ?>" class="<?php echo $btnClass; ?> backBtn">
                <?=$languageService->translate('back')?>
            </a>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
