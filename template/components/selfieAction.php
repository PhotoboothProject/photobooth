<?php

use Photobooth\Service\LanguageService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\ComponentUtility;

$languageService = LanguageService::getInstance();
$printManager = PrintManagerService::getInstance();
?>
<div class="buttonbar">
    <form id="selfieForm" enctype="multipart/form-data" style="display: none;">
        <input type="file" name="images[]" id="images" accept="image/*" capture="camera" required>
    </form>

    <label class="button take-selfie-btn" for="images" data-command="take-selfie">
        <span class="button--icon"><i class="<?= $config['icons']['take_picture'] ?>"></i></span>
        <span class="button--label"><?= $languageService->translate('takeSelfie') ?></span>
    </label>

    <?= ComponentUtility::renderButton('upload', 'fa fa-upload', 'selfie-submit', true, [
        'id' => 'selfieSubmitBtn',
        'style' => 'display: none;'
    ]) ?>

    <?= ComponentUtility::renderButton('abort', 'fa fa-xmark', 'selfie-abort', true, [
        'id' => 'selfieAbortBtn',
        'style' => 'display: none;'
    ]) ?>

    <?php if ($config['button']['reload']): ?>
        <?= ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'reload') ?>
    <?php endif; ?>

    <?php if ($config['gallery']['enabled']): ?>
        <?= ComponentUtility::renderButton('gallery', $config['icons']['gallery'], 'gallery-button') ?>
    <?php endif; ?>

    <?php if ($config['button']['show_cups']): ?>
        <?= ComponentUtility::renderButton('cups', $config['icons']['cups'], 'cups-button') ?>
    <?php endif; ?>

    <?php if ($config['button']['show_printUnlock']): ?>
        <?= ComponentUtility::renderButton(
            'reset_lock',
            $config['icons']['print'],
            'print-unlock-button',
            true,
            $printManager->isPrintLocked() ? [] : ['class' => 'hidden']
        ) ?>
    <?php endif; ?>
</div>
