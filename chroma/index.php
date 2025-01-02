<?php

require_once '../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\ComponentUtility;
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
$pageTitle = 'Chroma capture - ' . ApplicationService::getInstance()->getTitle();
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body class="gallery-mode--overlay ">

<?php include PathUtility::getAbsolutePath('template/components/preview.php'); ?>
<?php include PathUtility::getAbsolutePath('template/components/chroma.canvas.php'); ?>

<div class="stage stage--chroma rotarygroup" data-stage="start">
    <div class="stage-inner">
        <div class="stage-message stage-message--error"><?=$languageService->translate('chromaInfoBefore')?></div>
        <?php include PathUtility::getAbsolutePath('template/components/chroma.background.selector.php'); ?>
        <div class="buttonbar buttonbar--top">
            <?= ($config['gallery']['enabled']) ? ComponentUtility::renderButton('gallery', $config['icons']['gallery'], 'gallery-button') : '' ?>
        </div>
        <div class="buttonbar buttonbar--bottom">
            <?= ComponentUtility::renderButton('takePhoto', $config['icons']['take_picture'], 'take-chroma') ?>
        </div>
    </div>
</div>
<?php include PathUtility::getAbsolutePath('template/components/stage.loader.php'); ?>
<div class="stage stage--result" data-stage="result">
    <div class="stage-inner">
        <div class="buttonbar buttonbar--bottom">
            <?= ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'reload') ?>
            <?= ($config['qr']['enabled']) ? ComponentUtility::renderButton('qr', $config['icons']['qr'], 'qrbtn') : '' ?>
            <?= ($config['print']['from_chromakeying']) ? ComponentUtility::renderButton('print', $config['icons']['print'], 'print-btn') : '' ?>
            <?= ($config['picture']['allow_delete']) ? ComponentUtility::renderButton('delete', $config['icons']['delete'], 'deletebtn') : '' ?>
        </div>
    </div>
</div>

<?php include PathUtility::getAbsolutePath('template/components/gallery.php'); ?>
<script type="text/javascript">
    onCaptureChromaView = true;
</script>

<?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
<?php include PathUtility::getAbsolutePath('template/components/chroma.footer.php'); ?>

<script src="<?=$assetService->getUrl('resources/js/preview.js')?>"></script>
<script src="<?=$assetService->getUrl('resources/js/virtualKeyboard.js')?>"></script>
<script src="<?=$assetService->getUrl('resources/js/core.js')?>"></script>

<?php ProcessService::getInstance()->boot(); ?>
</body>
</html>
