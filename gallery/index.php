<?php

require_once '../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\AssetService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\PathUtility;

$assetService = AssetService::getInstance();
$pageTitle = 'Gallery - ' . ApplicationService::getInstance()->getTitle();
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>
<body>
    <?php include PathUtility::getAbsolutePath('template/components/gallery.php'); ?>

    <script>
        onStandaloneGalleryView = true;
    </script>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script src="<?=$assetService->getUrl('resources/js/preview.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/virtualKeyboard.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/core.js')?>"></script>
    <script src="<?=$assetService->getUrl('resources/js/gallery.js')?>"></script>
    <?php if ($config['gallery']['db_check_enabled']): ?>
    <script src="<?=$assetService->getUrl('resources/js/gallery-updatecheck.js')?>"></script>
    <?php endif; ?>

    <?php ProcessService::getInstance()->boot(); ?>
</body>
</html>
