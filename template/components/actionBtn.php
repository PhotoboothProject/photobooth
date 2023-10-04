<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;

$languageService = LanguageService::getInstance();

echo '<div class="buttonbar">';
if ($config['button']['force_buzzer']) {
    echo '<div id="useBuzzer" class="mt-4 mb-2 text-lg lg:text-3xl">'
        . $languageService->translate('use_button')
        . '</div>';
} else {
    if ($config['picture']['enabled']) {
        echo ComponentUtility::renderButton('takePhoto', $config['icons']['take_picture'], 'takePic');
    }
    if ($config['custom']['enabled']) {
        echo ComponentUtility::renderButton($config['custom']['btn_text'], $config['icons']['take_custom'], 'takeCustom');
    }
    if ($config['collage']['enabled']) {
        echo ComponentUtility::renderButton('takeCollage', $config['icons']['take_collage'], 'takeCollage');
    }
    if ($config['video']['enabled']) {
        echo ComponentUtility::renderButton('takeVideo', $config['icons']['take_video'], 'takeVideo');
    }
}
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
