<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';
require_once $fileRoot . 'lib/filter.php';
require_once $fileRoot . 'lib/applyEffects.php';
require_once $fileRoot . 'lib/image.php';

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$demoImage = 'adi-goldstein-Hli3R6LKibo-unsplash.jpg';

$imageHandler = new Image();
$imageHandler->debugLevel = $config['dev']['loglevel'];
$imageHandler->imageModified = false;

$imageResource = $imageHandler->createFromImage($demoFolder . DIRECTORY_SEPARATOR . $demoImage);
if (!$imageResource) {
    throw new Exception('Error creating image resource.');
}
$imageHandler->framePath = $config['picture']['frame'];

if ($config['picture']['flip'] !== 'off') {
    try {
        if ($config['picture']['flip'] === 'horizontal') {
            imageflip($imageResource, IMG_FLIP_HORIZONTAL);
        } elseif ($config['picture']['flip'] === 'vertical') {
            imageflip($imageResource, IMG_FLIP_VERTICAL);
        } elseif ($config['picture']['flip'] === 'both') {
            imageflip($imageResource, IMG_FLIP_BOTH);
        }
        $imageHandler->imageModified = true;
    } catch (Exception $e) {
        throw new Exception('Error flipping image.');
    }
}

// apply filter
$image_filter = $config['filters']['defaults'];
if ($image_filter) {
    try {
        applyFilter($image_filter, $imageResource);
        $imageHandler->imageModified = true;
    } catch (Exception $e) {
        throw new Exception('Error applying image filter.');
    }
}

if ($config['picture']['polaroid_effect']) {
    $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
    $imageResource = $imageHandler->effectPolaroid($imageResource);
    if (!$imageResource) {
        throw new Exception('Error applying polaroid effect.');
    }
}

if ($config['picture']['take_frame']) {
    $imageHandler->frameExtend = $config['picture']['extend_by_frame'];
    if ($config['picture']['extend_by_frame']) {
        $imageHandler->frameExtendLeft = $config['picture']['frame_left_percentage'];
        $imageHandler->frameExtendRight = $config['picture']['frame_right_percentage'];
        $imageHandler->frameExtendBottom = $config['picture']['frame_bottom_percentage'];
        $imageHandler->frameExtendTop = $config['picture']['frame_top_percentage'];
    }
    $imageResource = $imageHandler->applyFrame($imageResource);
    if (!$imageResource) {
        throw new Exception('Error applying frame to image resource.');
    }
}

if ($config['picture']['rotation'] !== '0') {
    $imageHandler->resizeRotation = $config['picture']['rotation'];
    $imageResource = $imageHandler->rotateResizeImage($imageResource);
    if (!$imageResource) {
        throw new Exception('Error resizing resource.');
    }
}

if ($config['textonpicture']['enabled']) {
    $imageHandler->fontSize = $config['textonpicture']['font_size'];
    $imageHandler->fontRotation = $config['textonpicture']['rotation'];
    $imageHandler->fontLocationX = $config['textonpicture']['locationx'];
    $imageHandler->fontLocationY = $config['textonpicture']['locationy'];
    $imageHandler->fontColor = $config['textonpicture']['font_color'];
    $imageHandler->fontPath = $config['textonpicture']['font'];
    $imageHandler->textLine1 = $config['textonpicture']['line1'];
    $imageHandler->textLine2 = $config['textonpicture']['line2'];
    $imageHandler->textLine3 = $config['textonpicture']['line3'];
    $imageHandler->textLineSpacing = $config['textonpicture']['linespace'];
    $imageResource = $imageHandler->applyText($imageResource);
    if (!$imageResource) {
        throw new Exception('Error applying text to image resource.');
    }
}

header('Content-Type: image/jpeg');
imagejpeg($imageResource);
imagedestroy($imageResource);
