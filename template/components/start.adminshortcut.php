<?php

use Photobooth\Utility\PathUtility;

if ($config['ui']['admin_shortcut'] ?? null) {
    echo '<!-- Admin Shortcut Enabled -->';
    echo '<button type="button" class="adminshortcut adminshortcut--' . ($config['ui']['admin_shortcut_position'] ?? 'bottom-right') . '" onclick="adminsettings()"></button>';
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'resources/js/adminshortcut.js?v=' . $config['photobooth']['version'] . '"></script>';
}
