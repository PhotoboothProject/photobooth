<?php
require_once '../lib/config.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;

    $extension = pathinfo($path)['extension'];
    if ($config['download']['thumbs'] && $extension !== 'mp4' && $extension !== 'gif') {
        $filename_source = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
    echo file_get_contents($path);
    exit();
}
