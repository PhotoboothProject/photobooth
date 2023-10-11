<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();

?>
<div id="gallery" class="gallery rotarygroup">
    <div class="gallery-header">
        <div class="gallery-title"><h1><?= $languageService->translate('gallery') ?></h1></div>
        <div class="gallery-actions">
            <?= ComponentUtility::renderButton('close', $config['icons']['close'], 'gallery__close') ?>
            <?= ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'gallery__refresh', true, ['class' => 'hidden']) ?>
        </div>
    </div>
    <div class="gallery-body">
        <?php include PathUtility::getAbsolutePath('template/components/gallery.images.php'); ?>
    </div>
</div>
