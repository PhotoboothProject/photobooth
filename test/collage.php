<?php

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Utility\ImageUtility;

$demoImages = ImageUtility::getDemoImages($config['collage']['limit']);

$name = date('Ymd_His') . '.jpg';
$collageSrcImagePaths = [];
for ($i = 0; $i < $config['collage']['limit']; $i++) {
    $image = $demoImages[$i];
    $path = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $i . '_' . $name;
    if (!copy($image, $path)) {
        throw new \Exception('Failed to copy image.');
    }
    $collageSrcImagePaths[] = $path;
}

$filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . 'result_' . $name;
if (Collage::createCollage($config, $collageSrcImagePaths, $filename_tmp, $config['filters']['defaults'])) {
    for ($k = 0; $k < $config['collage']['limit']; $k++) {
        unlink($collageSrcImagePaths[$k]);
    }
    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $imageHandler->imageModified = false;

    $imageResource = $imageHandler->createFromImage($filename_tmp);
    if (!$imageResource) {
        throw new \Exception('Error creating image resource.');
    }

    header('Content-Type: image/jpeg');

    imagejpeg($imageResource);
    unset($imageResource);
    $imageHandler = null;
    unlink($filename_tmp);
}
