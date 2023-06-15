<?php

include 'main.defaults.php';

echo '<!DOCTYPE html>' . "\n";
echo '<html>' . "\n";

echo '<head>' . "\n";

echo '<meta charset="UTF-8" />' . "\n";
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">' . "\n";
echo '<meta name="msapplication-TileColor" content="' . $config['colors']['primary'] . '">' . "\n";
echo '<meta name="theme-color" content="' . $config['colors']['primary'] . '">' . "\n";

echo '<title>' . $pageTitle . '</title>' . "\n";

echo '<!-- Favicon + Android/iPhone Icons -->' . "\n";
echo '<link rel="apple-touch-icon" sizes="180x180" href="' . $fileRoot . 'resources/img/apple-touch-icon.png">' . "\n";
echo '<link rel="icon" type="image/png" sizes="32x32" href="' . $fileRoot . 'resources/img/favicon-32x32.png">' . "\n";
echo '<link rel="icon" type="image/png" sizes="16x16" href="' . $fileRoot . 'resources/img/favicon-16x16.png">' . "\n";
echo '<link rel="manifest" href="' . $fileRoot . 'resources/img/site.webmanifest">' . "\n";
echo '<link rel="mask-icon" href="' . $fileRoot . 'resources/img/safari-pinned-tab.svg" color="#5bbad5">' . "\n";

echo '<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->' . "\n";
echo '<meta name="apple-mobile-web-app-capable" content="yes" />' . "\n";
echo '<meta name="apple-mobile-web-app-status-bar-style" content="black" />' . "\n";

echo '<link rel="stylesheet" href="' . $fileRoot . 'node_modules/normalize.css/normalize.css" />' . "\n";
echo '<link rel="stylesheet" href="' . $fileRoot . 'node_modules/font-awesome/css/font-awesome.css" />' . "\n";
echo '<link rel="stylesheet" href="' . $fileRoot . 'node_modules/material-icons/iconfont/material-icons.css">' . "\n";
echo '<link rel="stylesheet" href="' . $fileRoot . 'node_modules/material-icons/css/material-icons.css">' . "\n";

if ($photoswipe) {
    echo '<link rel="stylesheet" href="' . $fileRoot . 'node_modules/photoswipe/dist/photoswipe.css"/>' . "\n";
}
echo '<link rel="stylesheet" href="' . $fileRoot . 'resources/css/' . $mainStyle . '?v=' . $config['photobooth']['version'] . '"/>' . "\n";

if ($photoswipe && $config['gallery']['bottom_bar']) {
    echo '<link rel="stylesheet" href="' . $fileRoot . 'resources/css/photoswipe-bottom.css?v=' . $config['photobooth']['version'] . '"/>' . "\n";
}

if (is_file($fileRoot . 'private/overrides.css')) {
    echo '<link rel="stylesheet" href="' . $fileRoot . 'private/overrides.css?v=' . $config['photobooth']['version'] . '"/>' . "\n";
}
echo '</head>' . "\n";
?>
