<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Remote Trigger';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');

echo '<body>';
echo '<div id="wrapper">';
echo '<div id="trigger">';
echo '<div class="buttonbar">';

if ($config['remotebuzzer']['usebuttons']) {
    if ($config['picture']['enabled'] && $config['remotebuzzer']['picturebutton']) {
        echo ComponentUtility::renderButton('takePhoto', $config['icons']['take_picture'], 'remotebuzzer', true, ['data-action' => 'start-picture']);
    }
    if ($config['collage']['enabled'] && $config['remotebuzzer']['collagebutton']) {
        echo ComponentUtility::renderButton('takeCollage', $config['icons']['take_collage'], 'remotebuzzer', true, ['data-action' => 'start-collage']);
    }
    if ($config['custom']['enabled'] && $config['remotebuzzer']['custombutton']) {
        echo ComponentUtility::renderButton($config['custom']['btn_text'], $config['icons']['take_custom'], 'remotebuzzer', true, ['data-action' => 'start-custom']);
    }
    if ($config['custom']['enabled'] && $config['remotebuzzer']['custombutton']) {
        echo ComponentUtility::renderButton($config['custom']['btn_text'], $config['icons']['take_custom'], 'remotebuzzer', true, ['data-action' => 'start-custom']);
    }
    if ($config['video']['enabled'] && $config['remotebuzzer']['videobutton']) {
        ComponentUtility::renderButton('takeVideo', $config['icons']['take_video'], 'remotebuzzer', true, ['data-action' => 'start-video']);
    }
    if ($config['print']['from_result'] && $config['remotebuzzer']['printbutton']) {
        echo ComponentUtility::renderButton('print', $config['icons']['print'], 'remotebuzzer', true, ['data-action' => 'start-print']);
    }
    if ($config['remotebuzzer']['rebootbutton']) {
        echo ComponentUtility::renderButton('reboot_button', 'fa fa-exclamation-triangle', 'remotebuzzer', true, ['data-action' => 'reboot-now']);
    }
    if ($config['remotebuzzer']['shutdownbutton']) {
        echo ComponentUtility::renderButton('shutdown_button', 'fa fa-exclamation-triangle', 'remotebuzzer', true, ['data-action' => 'shutdown-now']);
    }
}

if ($config['remotebuzzer']['userotary']) {
    echo ComponentUtility::renderButton('previous_element', 'fa fa-chevron-left', 'remotebuzzer', true, ['data-action' => 'rotary-ccw']);
    echo ComponentUtility::renderButton('next_element', 'fa fa-chevron-right', 'remotebuzzer', true, ['data-action' => 'rotary-cw']);
    echo ComponentUtility::renderButton('click_element', 'fa fa-circle', 'remotebuzzer', true, ['data-action' => 'rotary-btn-press']);
}

echo ComponentUtility::renderButtonLink('back', 'fa fa-chevron-left', PathUtility::getPublicPath('test'));

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

include PathUtility::getAbsolutePath('template/components/main.footer.php');

echo '</body>';
echo '</html>';
