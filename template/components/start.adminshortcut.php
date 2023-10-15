<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

if ($config['ui']['admin_shortcut'] ?? null) {
    echo '<!-- Admin Shortcut Enabled -->';
    echo '<button type="button" class="adminshortcut adminshortcut--' . ($config['ui']['admin_shortcut_position'] ?? 'bottom-right') . '" onclick="adminsettings()"></button>';
    echo '<script src="' . $assetService->getUrl('resources/js/adminshortcut.js') . '"></script>';
}
