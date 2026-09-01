<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

echo '<script src="' . $assetService->getUrl('api/settings.php') . '"></script>';
echo '<script src="' . $assetService->getUrl('node_modules/lucide/dist/umd/lucide.min.js') . '"></script>';
echo '<script src="' . $assetService->getUrl('node_modules/iconify-icon/dist/iconify-icon.min.js') . '"></script>';
echo '<script src="' . $assetService->getUrl('resources/js/main.admin.js') . '"></script>';
echo '<script src="' . $assetService->getUrl('assets/js/admin/iconSelect.js') . '"></script>';
