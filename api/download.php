<?php

/** @var array $config */

use Photobooth\Enum\FolderEnum;

require_once '../lib/boot.php';

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if ($image) {
    $basename = basename($image);
    if ($basename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $basename)) {
        http_response_code(400);
        echo 'Invalid image name.';
        exit();
    }

    $path = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $basename;

    if (!is_file($path)) {
        http_response_code(404);
        echo $image . ' does not exist.';
        exit();
    }

    try {
        $pathinfo = pathinfo($path);

        if (!isset($pathinfo['extension'])) {
            throw new \Exception('Extension not found!');
        }
        $extension = $pathinfo['extension'];
        if ($config['download']['thumbs'] && $extension !== 'mp4' && $extension !== 'gif') {
            $thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $image;
            if (is_file($thumb)) {
                $path = $thumb;
            }
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="photobooth-' . $image . '"');
        echo file_get_contents($path);
    } catch (\Exception $e) {
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
