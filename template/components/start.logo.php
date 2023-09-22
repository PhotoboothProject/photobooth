<?php

if ($config['logo']['enabled']) {
    $logoClasses = 'w-full h-auto max-w-[30vw] absolute';
    if ($config['logo']['position'] == 'top_left') {
        $logoClasses .= ' top-4 left-4';
    } elseif ($config['logo']['position'] == 'top_right') {
        $logoClasses .= ' top-4 right-4';
    } elseif ($config['logo']['position'] == 'center') {
        $logoClasses .= ' my-auto top-1/2 -translate-y-1/2 -mt-12';
    } elseif ($config['logo']['position'] == 'bottom_left') {
        $logoClasses .= ' bottom-4 left-4';
    } elseif ($config['logo']['position'] == 'bottom_right') {
        $logoClasses .= ' bottom-4 right-4';
    }
    echo '<div id="pblogo" class="' .
        $logoClasses .
        ' pblogo--div">
        <img class="w-full h-full object-contain pblogo--img" src="' .
        $config['logo']['path'] .
        '" alt="logo">
</div>';
}
