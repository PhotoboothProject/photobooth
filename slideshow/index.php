<?php

require_once '../lib/boot.php';

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Slideshow';
$photoswipe = true;
$randomImage = $config['slideshow']['randomPicture'];
$remoteBuzzer = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body>
    <div id="gallery" class="gallery">
        <div class="gallery-header">
            <div class="gallery-title"><h1><?=$languageService->translate('slideshow')?></h1></div>
        </div>
        <div class="gallery-body">
            <?php include PathUtility::getAbsolutePath('template/components/gallery.images.php'); ?>
        </div>
    </div>
    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <script src="<?=$assetService->getUrl('resources/js/slideshow.js')?>"></script>
</body>
</html>
