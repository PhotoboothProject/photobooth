<?php

echo '<script src="' . $fileRoot . 'node_modules/whatwg-fetch/dist/fetch.umd.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'api/config.php?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'node_modules/jquery/dist/jquery.min.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/tools.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/theme.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";
echo '<script src="' . $fileRoot . 'node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>' . "\n";
echo '<script type="text/javascript" src="' . $fileRoot . 'resources/js/i18n.js?v=' . $config['photobooth']['version'] . '"></script>' . "\n";

?>
