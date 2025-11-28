<?php

use Photobooth\Environment;

require_once '../lib/boot.php';

use Photobooth\Utility\PathUtility;

header('Content-Type: application/javascript');

// Override secret configuration we don't need access from javascript for
$config['mail']['password'] = 'secret';
$config['login']['username'] = 'secret';
$config['login']['password'] = 'secret';
$config['login']['pin'] = 'secret';
$config['ftp']['username'] = 'secret';
$config['ftp']['password'] = 'secret';

if (!empty($config['logo']['path'])) {
    $config['logo']['path'] = PathUtility::getPublicPath($config['logo']['path']);
}
if (!empty($config['ui']['shutter_cheese_img'])) {
    $config['ui']['shutter_cheese_img'] = PathUtility::getPublicPath($config['ui']['shutter_cheese_img']);
}
if (!empty($config['picture']['frame'])) {
    $config['picture']['frame'] = PathUtility::getPublicPath($config['picture']['frame']);
}
if (!empty($config['collage']['frame'])) {
    $config['collage']['frame'] = PathUtility::getPublicPath($config['collage']['frame']);
}
if (!empty($config['background']['defaults'])) {
    $config['background']['defaults'] = PathUtility::getPublicPath($config['background']['defaults']);
}
if (!empty($config['background']['admin'])) {
    $config['background']['admin'] = PathUtility::getPublicPath($config['background']['admin']);
}
if (!empty($config['background']['chroma'])) {
    $config['background']['chroma'] = PathUtility::getPublicPath($config['background']['chroma']);
}

echo 'const config = ' . json_encode($config) . ';';
echo 'const environment = ' . json_encode(new Environment()) . ';';
