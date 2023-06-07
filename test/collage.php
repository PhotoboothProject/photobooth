<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';
require_once $fileRoot . 'lib/collage.php';
require_once $fileRoot . 'lib/image.php';

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$demoImages = [
    'adi-goldstein-Hli3R6LKibo-unsplash.jpg',
    'alasdair-elmes-ULHxWq8reao-unsplash.jpg',
    'elena-de-soto-w423NnHFjFg-unsplash.jpg',
    'matty-adame-nLUb9GThIcg-unsplash.jpg',
];

$name = date('Ymd_His') . '.jpg';
$collageSrcImagePaths = [];
for ($i = 0; $i < $config['collage']['limit']; $i++) {
    $image = $demoImages[$i];
    $path = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $i . '_' . $name;
    copy($demoFolder . DIRECTORY_SEPARATOR . $image, $path);
    $collageSrcImagePaths[] = $path;
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'result_' . $name;
if (createCollage($collageSrcImagePaths, $filename_tmp, $config['filters']['defaults'])) {
    for ($k = 0; $k < $config['collage']['limit']; $k++) {
        unlink($collageSrcImagePaths[$k]);
    }
    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $imageHandler->imageModified = false;

    $imageResource = $imageHandler->createFromImage($filename_tmp);
    if (!$imageResource) {
        throw new Exception('Error creating image resource.');
    }

    header('Content-Type: image/jpeg');

    imagejpeg($imageResource);
    imagedestroy($imageResource);
    $imageHandler = null;
    unlink($filename_tmp);
}
