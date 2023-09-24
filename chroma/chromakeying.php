<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

if (empty($_GET['filename'])) {
    die('No or invalid file provided');
}

$filename = $_GET['filename'];
$mainimage = PathUtility::getPublicPath() . $config['foldersRoot']['keying'] . DIRECTORY_SEPARATOR . $filename;
$imginfo = @getimagesize($config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $filename);

if (is_array($imginfo)) {
    // Only jpg/jpeg are supported
    $mimetype = isset($imginfo['mime']) ? $imginfo['mime'] : 'unknown';
    if ($mimetype == 'image/jpg' || $mimetype == 'image/jpeg') {
        $keying_possible = true;
    } else {
        $keying_possible = false;
        $mainimage = PathUtility::getPublicPath('resources/img/bg.jpg');
    }
} else {
    $keying_possible = false;
    $mainimage = PathUtility::getPublicPath('resources/img/bg.jpg');
}

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Chromakeying';
$mainStyle = $config['ui']['style'] . '_chromakeying.css';
$photoswipe = false;
$remoteBuzzer = true;
$chromaKeying = true;
$GALLERY_FOOTER = true;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body data-main-image="<?=$mainimage?>">
    <div class="chromawrapper rotarygroup">
    <?php if ($keying_possible): ?>
        <div class="canvasWrapper <?php echo $uiShape; ?> noborder initial">
            <canvas class="<?php echo $uiShape; ?>" id="mainCanvas"></canvas>
        </div>

        <div style="padding-top:10px;text-align:center;">
        <?php
        $backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($config['keying']['background_path']));
        foreach ($backgroundImages as $backgroundImage) {
            echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="backgroundPreview ' . $uiShape . ' rotaryfocus" onclick="setBackgroundImage(this.src)">';
        }
        ?>
        </div>

        <div class="chroma-control-bar">
            <a class="<?php echo $btnClass; ?> rotaryfocus" id="save-chroma-btn" href="#">
                <i class="<?php echo $config['icons']['save']; ?>"></i>
                <?=$languageService->translate('save')?>
            </a>
            <?php if ($config['print']['from_chromakeying']): ?>
                <a class="<?php echo $btnClass; ?> rotaryfocus" id="print-btn" href="#">
                    <i class="<?php echo $config['icons']['print']; ?>"></i>
                    <?=$languageService->translate('print')?>
                </a>
            <?php endif; ?>
            <a class="<?php echo $btnClass; ?> rotaryfocus" id="close-btn" href="#">
                <i class="<?php echo $config['icons']['close']; ?>"></i>
                <?=$languageService->translate('close')?>
            </a>
        </div>
    <?php else:?>
        <div style="text-align:center;padding-top:250px">
            <h1 style="color: red;"><?=$languageService->translate('keyingerror')?></h1>
            <a class="<?php echo $btnClass; ?>" href="<?=PathUtility::getPublicPath()?>"><?=$languageService->translate('close')?></a>
        </div>
    <?php endif; ?>

        <?php include PathUtility::getAbsolutePath('template/modal.template.php'); ?>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <?php require_once PathUtility::getAbsolutePath('lib/services_start.php'); ?>
</body>
</html>
