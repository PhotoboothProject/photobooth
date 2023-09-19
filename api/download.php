<?php

require_once '../lib/boot.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;

    if (!is_file($path)) {
        http_response_code(404);
        echo $image . ' does not exist.';
        exit();
    }

    try {
        $extension = pathinfo($path)['extension'];
        if ($config['download']['thumbs'] && $extension !== 'mp4' && $extension !== 'gif') {
            $thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;
            if (is_file($thumb)) {
                $path = $thumb;
            }
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
        echo file_get_contents($path);
    } catch (Exception $e) {
        http_response_code(500);
        echo 'Error downloading the file ' . $image;
        if ($config['dev']['loglevel'] > 1) {
            echo $e->getMessage();
        }
    }
} else {
    http_response_code(400);
    echo 'No image defined.';
}
exit();
