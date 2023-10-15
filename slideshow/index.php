<?php

require_once '../lib/boot.php';

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Slideshow';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = true;
$randomImage = $config['slideshow']['randomPicture'];
$remoteBuzzer = false;
$chromaKeying = false;
$GALLERY_FOOTER = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body class="deselect">
    <div id="wrapper">
        <div id="gallery" class="gallery">
            <div class="gallery__inner">
                <div class="gallery__header">
                    <h1><?=$languageService->translate('slideshow')?></h1>
                </div>
                <?php include PathUtility::getAbsolutePath('template/components/gal.images.php'); ?>
            </div>
        </div>
    </div>
    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <script src="<?=$assetService->getUrl('resources/js/slideshow.js')?>"></script>
</body>
</html>
