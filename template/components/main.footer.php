<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

echo '<script src="' . $assetService->getUrl('api/config.php') . '"></script>';
echo '<script src="' . $assetService->getUrl('resources/js/tools.js') . '"></script>';

if ($remoteBuzzer) {
    echo '<script src="' . $assetService->getUrl('node_modules/socket.io-client/dist/socket.io.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('resources/js/remotebuzzer_client.js') . '"></script>';
}

if ($photoswipe) {
    echo '<script src="' . $assetService->getUrl('node_modules/photoswipe/dist/umd/photoswipe.umd.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('resources/js/photoswipe.js') . '"></script>';
}

if ($chromaKeying) {
    if ($config['keying']['variant'] === 'marvinj') {
        echo '<script src="' . $assetService->getUrl('node_modules/marvinj/marvinj/release/marvinj-1.0.js') . '"></script>';
    } else {
        echo '<script src="' . $assetService->getUrl('vendor/Seriously/seriously.js') . '"></script>';
        echo '<script src="' . $assetService->getUrl('vendor/Seriously/effects/seriously.chroma.js') . '"></script>';
    }
    echo '<script src="' . $assetService->getUrl('resources/js/chromakeying.js') . '"></script>';
}
