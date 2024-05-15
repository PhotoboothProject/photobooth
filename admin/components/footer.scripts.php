<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

echo '<script src="' . $assetService->getUrl('api/settings.php') . '"></script>';
echo '<script src="' . $assetService->getUrl('resources/js/main.admin.js') . '"></script>';
