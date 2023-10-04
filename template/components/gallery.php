<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();

?>
<div id="gallery" class="gallery rotarygroup">
    <div class="gallery__inner">
        <div class="gallery__header">
            <h1><?= $languageService->translate('gallery') ?></h1>
            <?= ComponentUtility::renderButton('close', $config['icons']['close'], 'gallery__close') ?>
            <?= ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'gallery__refresh', true, ['class' => 'hidden']) ?>
        </div>
        <?php include PathUtility::getAbsolutePath('template/components/gal.images.php'); ?>
        <?php if ($GALLERY_FOOTER === true && $config['gallery']['action_footer'] === true) {
            include PathUtility::getAbsolutePath('template/components/gal.btnFooter.php');
        } ?>
    </div>
</div>

