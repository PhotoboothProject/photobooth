<?php
if ($config['ui']['admin_shortcut']) {
    $shortcutClasses = 'w-16 h-16 sm:w-24 sm:h-24 absolute z-50 ';
    if ($config['ui']['admin_shortcut_position'] == 'top-left') {
        $shortcutClasses .= 'top-0 left-0';
    } elseif ($config['ui']['admin_shortcut_position'] == 'top-right') {
        $shortcutClasses .= 'top-0 right-0';
    } elseif ($config['ui']['admin_shortcut_position'] == 'bottom-left') {
        $shortcutClasses .= 'bottom-0 left-0';
    } else {
        $shortcutClasses .= 'bottom-0 right-0';
    }
    echo '<div class="' . $shortcutClasses . '" onclick="adminsettings()"></div>';
}
?>
