<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Slideshow';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = true;
$randomImage = $config['slideshow']['randomPicture'];
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body>
    <div id="wrapper">
        <div id="gallery" class="gallery">
            <div class="gallery-header">
                <div class="gallery-title"><h1><?=$languageService->translate('slideshow')?></h1></div>
            </div>
            <div class="gallery-body">
                <?php include PathUtility::getAbsolutePath('template/components/gallery.images.php'); ?>
            </div>
        </div>
    </div>
    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/slideshow.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
</body>
</html>
