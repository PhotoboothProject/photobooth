<?php
require_once '../lib/config.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $fullres = $config['foldersRoot']['images'] . DIRECTORY_SEPARATOR . $image;
    $thumbres = $config['foldersRoot']['thumbs'] . DIRECTORY_SEPARATOR . $image;

    $pathInfo = pathinfo($fullres);
    if ($pathInfo['extension'] !== 'mp4' && $config['download']['thumbs']) {
        $filename_source = $thumbres;
    } else {
        $filename_source = $fullres;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($fullres));
    header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
    echo file_get_contents(__DIR__ . '/../' . $filename_source);
    exit();
}
