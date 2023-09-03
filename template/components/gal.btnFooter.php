<?php
echo '<div class="gallery__footer">' . "\n";
echo '<div class="buttongroup">' . "\n";

if ($config['button']['force_buzzer']) {
    echo '<div id="useBuzzer">
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

echo '</div>' . "\n";
echo '</div>' . "\n";
?>

