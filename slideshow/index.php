<?php

require_once '../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\LoginMenuUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = 'Slideshow - ' . ApplicationService::getInstance()->getTitle();
$photoswipe = true;
$randomImage = $config['slideshow']['randomPicture'];
$remoteBuzzer = false;
$standaloneReturnUrl = LoginMenuUtility::isMenuSessionActive($_SESSION) ? PathUtility::getPublicPath('login') : null;

include PathUtility::getAbsolutePath('template/components/main.head.php');

?>
<body>
    <div id="gallery" class="gallery">
        <div class="gallery-header">
            <div class="gallery-title"><h1><?=$languageService->translate('slideshow')?></h1></div>
            <div class="gallery-actions">
                <?php if ($standaloneReturnUrl !== null): ?>
                    <?= \Photobooth\Utility\ComponentUtility::renderButtonLink('back', 'fa fa-chevron-left', $standaloneReturnUrl) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="gallery-body">
            <?php include PathUtility::getAbsolutePath('template/components/gallery.images.php'); ?>
        </div>
    </div>
    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
    <script>
        standaloneReturnUrl = <?= json_encode($standaloneReturnUrl) ?>;
    </script>
    <script src="<?=$assetService->getUrl('resources/js/slideshow.js')?>"></script>
</body>
</html>
