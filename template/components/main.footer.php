<?php

echo '<script src="' . $fileRoot . 'node_modules/whatwg-fetch/dist/fetch.umd.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'api/config.php?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/jquery/dist/jquery.min.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/tools.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/theme.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script src="' . $fileRoot . 'node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/i18n.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";

if ($remoteBuzzer) {
    echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/socket.io-client/dist/socket.io.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/remotebuzzer_client.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}

if ($photoswipe) {
    echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/photoswipe.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}

if ($chromaKeying) {
    if ($config['keying']['variant'] === 'marvinj') {
        echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>' . "\n";
    } else {
        echo '<script type="text/javascript" src="' . $fileRoot . 'vendor/Seriously/seriously.js"></script>' . "\n";
        echo '<script type="text/javascript" src="' . $fileRoot . 'vendor/Seriously/effects/seriously.chroma.js"></script>' . "\n";
    }
    echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/chromakeying.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}

?>
