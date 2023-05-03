<?php
require_once '../lib/config.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;

    if (!is_file($path)) {
        http_response_code(404);
        echo $image . ' does not exist.';
        exit();
    }
    
    $extension = pathinfo($path)['extension'];
    if ($config['download']['thumbs'] && $extension !== 'mp4' && $extension !== 'gif') {
        $path = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
    echo file_get_contents($path);
    exit();
} else {
    http_response_code(400);
    echo 'No image defined.';
}
