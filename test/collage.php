<?php

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Utility\ImageUtility;

$demoFolder = 'resources/img/demo';
$devImg = ImageUtility::getImagesFromPath($demoFolder);

if (empty($devImg)) {
    throw new \Exception('No images found inside demo folders.');
}

$demoImages = [];
// Loop to select random images
for ($i = 0; $i < $config['collage']['limit']; $i++) {
    // Check if there are any images left to select
    if (empty($devImg)) {
        $devImg = $demoImages;
    }

    // Select a random index from the remaining images
    $randomIndex = array_rand($devImg);

    // Add the selected image to the $demoImages array
    $demoImages[] = $devImg[$randomIndex];

    // Remove the selected image from the $devImg array to avoid selecting it again
    unset($devImg[$randomIndex]);

    // Reset array keys to ensure consecutive integer keys
    $devImg = array_values($devImg);
}

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
