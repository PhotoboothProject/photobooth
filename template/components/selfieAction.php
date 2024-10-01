<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;

$languageService = LanguageService::getInstance();

echo '<div class="buttonbar">';

//
//  add form for selfie with button here
//
echo '<div class="container" id="form-container"></div>';

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
