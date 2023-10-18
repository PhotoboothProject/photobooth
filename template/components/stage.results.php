<?php

use Photobooth\Utility\ComponentUtility;

echo '<div class="stage rotarygroup" data-stage="result">';
echo '<div class="stage-inner">';
echo '<div class="buttonbar buttonbar--bottom">';

if (!$config['button']['force_buzzer']) {
    if ($config['picture']['enabled']) {
        echo ComponentUtility::renderButton('newPhoto', $config['icons']['take_picture'], 'newpic');
    }
    if ($config['custom']['enabled']) {
        echo ComponentUtility::renderButton('newCustom', $config['icons']['take_custom'], 'newcustom');
    }
    if ($config['collage']['enabled']) {
        echo ComponentUtility::renderButton('newCollage', $config['icons']['take_collage'], 'newcollage');
    }
    if ($config['video']['enabled']) {
        echo ComponentUtility::renderButton('newVideo', $config['icons']['take_video'], 'newvideo');
    }
}

if ($config['button']['homescreen']) {
    echo ComponentUtility::renderButton('home', $config['icons']['home'], 'homebtn');
}
if ($config['qr']['enabled']) {
    echo ComponentUtility::renderButton('qr', $config['icons']['qr'], 'qrbtn');
}
if ($config['gallery']['enabled']) {
    echo ComponentUtility::renderButton('gallery', $config['icons']['gallery'], 'gallerybtn');
}
if ($config['mail']['enabled']) {
    echo ComponentUtility::renderButton('mail', $config['icons']['mail'], 'mailbtn');
}
if ($config['print']['from_result']) {
    echo ComponentUtility::renderButton('print', $config['icons']['print'], 'printbtn');
}
if ($config['filters']['enabled']) {
    echo ComponentUtility::renderButton('selectFilter', $config['icons']['filter'], 'imageFilter');
}
if ($config['picture']['allow_delete']) {
    echo ComponentUtility::renderButton('delete', $config['icons']['delete'], 'deletebtn');
}

echo '</div>';
echo '</div>';
echo '</div>';
