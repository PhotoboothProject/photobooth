<?php

require_once '../lib/boot.php';

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Preview-Test';
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
<?php

echo ComponentUtility::renderButton('startPreview', 'fa fa-play', 'startPreview');
echo ComponentUtility::renderButton('stopPreview', 'fa fa-stop', 'stopPreview');
if ($config['preview']['showFrame'] && !empty($config['picture']['frame'])) {
    echo ComponentUtility::renderButton('showPictureFrame', 'fa fa-eye', 'showPictureFrame');
}
if ($config['preview']['showFrame'] && !empty($config['collage']['frame'])) {
    echo ComponentUtility::renderButton('showCollageFrame', 'fa fa-eye', 'showCollageFrame');
}
if ($config['preview']['showFrame'] && !empty($config['picture']['frame']) || $config['preview']['showFrame'] && !empty($config['collage']['frame'])) {
    echo ComponentUtility::renderButton('hideFrame', 'fa fa-eye-slash', 'hideFrame');
}

?>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <script src="<?=$assetService->getUrl('resources/js/preview.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/test-preview.js')?>"></script>
</body>
</html>
