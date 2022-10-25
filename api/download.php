<?php
require_once '../lib/config.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;

    $pathInfo = pathinfo($path);
    if ($pathInfo['extension'] !== 'mp4' && $config['download']['thumbs']) {
        $filename_source = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
    echo file_get_contents($path);
    exit();
}
