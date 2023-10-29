<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

echo '<script src="' . $assetService->getUrl('api/config.php') . '"></script>';
echo '<script src="' . $assetService->getUrl('resources/js/tools.js') . '"></script>';

if ($remoteBuzzer) {
    echo '<script src="' . $assetService->getUrl('node_modules/socket.io-client/dist/socket.io.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('resources/js/remotebuzzer-client.js') . '"></script>';
}

if ($photoswipe) {
    echo '<script src="' . $assetService->getUrl('node_modules/photoswipe/dist/umd/photoswipe.umd.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('resources/js/photoswipe.js') . '"></script>';
}
