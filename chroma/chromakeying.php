<?php

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

if (empty($_GET['filename'])) {
    die('No or invalid file provided');
}

$filename = $_GET['filename'];
$mainimage = PathUtility::getPublicPath(FolderEnum::KEYING->absolute() . DIRECTORY_SEPARATOR . $filename);
$imginfo = @getimagesize(FolderEnum::KEYING->absolute() . DIRECTORY_SEPARATOR . $filename);

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
$pageTitle = 'Chromakeying - ' . ApplicationService::getInstance()->getTitle();
$photoswipe = false;
$remoteBuzzer = true;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>

<body data-main-image="<?=$mainimage?>">
    <?php if ($keying_possible): ?>
        <?php include PathUtility::getAbsolutePath('template/components/chroma.canvas.php'); ?>
        <div class="stage stage--active stage--chromakeying rotarygroup" data-stage="start">
            <div class="stage-inner">
                <?php include PathUtility::getAbsolutePath('template/components/chroma.background.selector.php'); ?>
                <div class="buttonbar buttonbar--bottom">
                    <?= ComponentUtility::renderButton('save', $config['icons']['save'], 'save-chroma-btn'); ?>
                    <?= ($config['print']['from_chromakeying']) ? ComponentUtility::renderButton('print', $config['icons']['print'], 'print-btn') : '' ?>
                    <?= ComponentUtility::renderButton('close', $config['icons']['close'], 'close-btn'); ?>
                </div>
            </div>
        </div>
    <?php else:?>
        <div>
            <h1><?=$languageService->translate('keyingerror')?></h1>
            <?= ComponentUtility::renderButtonLink('close', $config['icons']['close'], PathUtility::getPublicPath()) ?>
        </div>
    <?php endif; ?>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <?php include PathUtility::getAbsolutePath('template/components/chroma.footer.php'); ?>

    <?php ProcessService::getInstance()->boot(); ?>
</body>
</html>
