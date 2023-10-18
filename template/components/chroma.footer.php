<?php

use Photobooth\Service\AssetService;

$assetService = AssetService::getInstance();

if ($config['keying']['variant'] === 'marvinj') {
    echo '<script src="' . $assetService->getUrl('node_modules/marvinj/marvinj/release/marvinj-1.0.js') . '"></script>';
} else {
    echo '<script src="' . $assetService->getUrl('vendor/Seriously/seriously.js') . '"></script>';
    echo '<script src="' . $assetService->getUrl('vendor/Seriously/effects/seriously.chroma.js') . '"></script>';
}
echo '<script src="' . $assetService->getUrl('resources/js/chromakeying.js') . '"></script>';
