<?php

use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();

use Photobooth\Utility\ComponentUtility;

echo '<div class="gallery__footer">';
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

echo '</div>';
echo '</div>';
