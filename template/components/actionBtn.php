<?php

if ($config['button']['force_buzzer']) {
    echo '<div id="useBuzzer" class="mt-4 mb-2 text-lg lg:text-3xl">
          <span data-i18n="use_button"></span>
          </div>';
} else {
    if ($config['picture']['enabled']) {
        echo getBoothButton('takePhoto', $config['icons']['take_picture'], 'takePic');
    }
    if ($config['custom']['enabled']) {
        echo getBoothButton($config['custom']['btn_text'], $config['icons']['take_custom'], 'takeCustom');
    }
    if ($config['collage']['enabled']) {
        echo getBoothButton('takeCollage', $config['icons']['take_collage'], 'takeCollage');
    }
    if ($config['video']['enabled']) {
        echo getBoothButton('takeVideo', $config['icons']['take_video'], 'takeVideo');
    }
}

if ($config['button']['reload']) {
    echo getBoothButton('reload', $config['icons']['refresh'], 'reload');
}

if ($config['gallery']['enabled']) {
    echo getBoothButton('gallery', $config['icons']['gallery'], 'gallery-button');
}

if ($config['button']['show_fs']) {
    echo getBoothButton('toggleFullscreen', $config['icons']['fullscreen'], 'fs-button');
}
if ($config['button']['show_cups']) {
    echo getBoothButton('cups', $config['icons']['cups'], 'cups-button');
}
