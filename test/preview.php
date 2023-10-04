<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Preview-Test';
$mainStyle = $config['ui']['style'] . '_style.css';
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
        <div class="buttonbar buttonbar--bottom">
<?php

echo ComponentUtility::renderButton('startPreview', 'fa fa-play', 'startPreview');
echo ComponentUtility::renderButton('stopPreview', 'fa fa-stop', 'stopPreview');
if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])) {
    echo ComponentUtility::renderButton('showPictureFrame', 'fa fa-eye', 'showPictureFrame');
}
if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])) {
    echo ComponentUtility::renderButton('showCollageFrame', 'fa fa-eye', 'showCollageFrame');
}
if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe']) || $config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])) {
    echo ComponentUtility::renderButton('hideFrame', 'fa fa-eye-slash', 'hideFrame');
}
echo ComponentUtility::renderButtonLink('back', 'fa fa-chevron-left', PathUtility::getPublicPath('test'));

?>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
