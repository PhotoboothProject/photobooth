<?php

if ($config['button']['force_buzzer']) {
    echo '<div id="useBuzzer">';
    echo '<span data-i18n="use_button"></span>';
    echo '</div>' . "\n";
} else {
    if ($config['picture']['enabled']) {
        echo '<a href="#" class="' . $btnClass . ' takePic rotaryfocus"><i class="' . $config['icons']['take_picture'] . '"></i> <span data-i18n="takePhoto"></span></a>' . "\n";
    }
    if ($config['custom']['enabled']) {
        echo '<a href="#" class="' .
            $btnClass .
            ' 
        takeCustom rotaryfocus"><i class="' .
            $config['icons']['take_custom'] .
            '"></i> <span>' .
            $config['custom']['btn_text'] .
            '</span></a>' .
            "\n";
    }
    if ($config['collage']['enabled']) {
        echo '<a href="#" class="' .
            $btnClass .
            ' takeCollage rotaryfocus"><i class="' .
            $config['icons']['take_collage'] .
            '"></i> <span data-i18n="takeCollage"></span></a>' .
            "\n";
    }
    if ($config['video']['enabled']) {
        echo '<a href="#" class="' .
            $btnClass .
            ' 
        takeVideo rotaryfocus"><i class="' .
            $config['icons']['take_video'] .
            '"></i> <span data-i18n="takeVideo"></span></a>' .
            "\n";
    }
    if ($config['button']['reload']) {
        echo '<a href="#" class="' .
            $btnClass .
            ' rotaryfocus" onclick="window.location.reload();"><i class="' .
            $config['icons']['refresh'] .
            '"></i> <span data-i18n="reload"></span></a>' .
            "\n";
    }
}
?>
