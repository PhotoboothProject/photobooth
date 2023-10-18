<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

if (empty($_GET['filename'])) {
    die('No or invalid file provided');
}

$filename = $_GET['filename'];
$mainimage = PathUtility::getPublicPath($config['foldersPublic']['keying'] . DIRECTORY_SEPARATOR . $filename);
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

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body data-main-image="<?=$mainimage?>">
    <div class="chromawrapper rotarygroup">
    <?php if ($keying_possible): ?>
        <div class="canvasWrapper initial">
            <canvas id="mainCanvas"></canvas>
        </div>

        <div style="padding-top:10px;text-align:center;">
        <?php
        $backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($config['keying']['background_path']));
        foreach ($backgroundImages as $backgroundImage) {
            echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="backgroundPreview rotaryfocus" onclick="setBackgroundImage(this.src)">';
        }
        ?>
        </div>

        <div class="chroma-control-bar">
<?php
    echo ComponentUtility::renderButton('save', $config['icons']['save'], 'save-chroma-btn');
        if ($config['print']['from_chromakeying']) {
            echo ComponentUtility::renderButton('print', $config['icons']['print'], 'print-btn');
        }
        echo ComponentUtility::renderButton('close', $config['icons']['close'], 'close-btn');
        ?>
        </div>
    <?php else:?>
        <div style="text-align:center;padding-top:250px">
            <h1 style="color: red;"><?=$languageService->translate('keyingerror')?></h1>
            <?= ComponentUtility::renderButtonLink('close', $config['icons']['close'], PathUtility::getPublicPath()) ?>
        </div>
    <?php endif; ?>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <?php include PathUtility::getAbsolutePath('template/components/chroma.footer.php'); ?>

    <?php require_once PathUtility::getAbsolutePath('lib/services_start.php'); ?>
</body>
</html>
