<?php

use Photobooth\Utility\PathUtility;

?>

<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>api/config.php?v=<?= $config['photobooth']['version'] ?>"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/tools.js?v=<?= $config['photobooth']['version'] ?>"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/theme.js?v=<?= $config['photobooth']['version'] ?>"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
<script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/i18n.js?v=<?= $config['photobooth']['version'] ?>"></script>

<?php

if ($remoteBuzzer) {
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'node_modules/socket.io-client/dist/socket.io.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'resources/js/remotebuzzer_client.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}

if ($photoswipe) {
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>' . "\n";
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'resources/js/photoswipe.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}

if ($chromaKeying) {
    if ($config['keying']['variant'] === 'marvinj') {
        echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>' . "\n";
    } else {
        echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'vendor/Seriously/seriously.js"></script>' . "\n";
        echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'vendor/Seriously/effects/seriously.chroma.js"></script>' . "\n";
    }
    echo '<script type="text/javascript" src="' . PathUtility::getPublicPath() . 'resources/js/chromakeying.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
}
