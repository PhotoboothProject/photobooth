<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;

$languageService = LanguageService::getInstance();

echo '<div class="buttonbar">';

echo '<div class="container" id="form-container">';
echo '    <form id="selfieForm" enctype="multipart/form-data">';
echo '       <label class="button take-selfie-btn" for="images" data-command="take-selfie">';
echo '            <span class="button--icon"><i class="' . $config['icons']['take_picture'] . '"></i></span>';
echo '            <span class="button--label">' . $languageService->translate('takeSelfie') . '</span>';
echo '        </label>';
echo '        <button type="button" class="button" id="submitBtn" style="display: none;">';
echo '            <span class="button--icon"><i class="fa fa-upload"></i></span>';
echo '            <span class="button--label">' . $languageService->translate('upload') . '</span>';
echo '        </button>';
echo '        <input type="file" name="images[]" id="images" accept="image/*" capture="camera" style="display: none;" required>';
echo '    </form>';
echo '</div>';

if ($config['button']['reload']) {
    echo ComponentUtility::renderButton('reload', $config['icons']['refresh'], 'reload');
}
if ($config['gallery']['enabled']) {
    echo ComponentUtility::renderButton('gallery', $config['icons']['gallery'], 'gallery-button');
}
if ($config['button']['show_fs']) {
    echo ComponentUtility::renderButton('toggleFullscreen', $config['icons']['fullscreen'], 'fs-button');
}
if ($config['button']['show_cups']) {
    echo ComponentUtility::renderButton('cups', $config['icons']['cups'], 'cups-button');
}
echo '</div>';
