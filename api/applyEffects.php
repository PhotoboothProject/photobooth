<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/polaroid.php';
require_once '../lib/resize.php';
require_once '../lib/collage.php';
require_once '../lib/applyText.php';
require_once '../lib/log.php';

if (!extension_loaded('gd')) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': GD library not loaded! Please enable GD!';
    logErrorAndDie($errormsg);
}

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

$file = $_POST['file'];

$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);
$chroma_size = substr($config['keying']['size'], 0, -2);

// text on picture variables
$fontpath = $config['textonpicture']['font'];
$fontcolor = $config['textonpicture']['font_color'];
$fontsize = $config['textonpicture']['font_size'];
$fontlocx = $config['textonpicture']['locationx'];
$fontlocy = $config['textonpicture']['locationy'];
$linespacing = $config['textonpicture']['linespace'];
$fontrot = $config['textonpicture']['rotation'];
$line1text = $config['textonpicture']['line1'];
$line2text = $config['textonpicture']['line2'];
$line3text = $config['textonpicture']['line3'];

$quality = 100;
$imageModified = false;
$image_filter = false;

if (!isset($_POST['style'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No style provided';
    logErrorAndDie($errormsg);
}

if (!isset($_POST['filter'])) {
    $ErrorData = [
        'warning' => 'No filter provided! Using plain image filter!',
    ];
    logError($ErrorData);
    $image_filter = 'plain';
}

if (!empty($_POST['filter']) && $_POST['filter'] !== 'plain') {
    $image_filter = $_POST['filter'];
}

// Check collage configuration
if ($_POST['style'] === 'collage') {
    if ($config['textoncollage']['enabled']) {
        testFile($config['textoncollage']['font']);
    }
}

$srcImages = [];
$srcImages[] = $file;

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

// Process Collage
if ($_POST['style'] === 'collage') {
    $collageBasename = substr($filename_tmp, 0, -4);
    $singleImageBase = substr($file, 0, -4);

    $collageSrcImagePaths = [];

    for ($i = 0; $i < $config['collage']['limit']; $i++) {
        $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
        if ($config['collage']['keep_single_images']) {
            $srcImages[] = $singleImageBase . '-' . $i . '.jpg';
        }
    }

    if (!createCollage($collageSrcImagePaths, $filename_tmp, $image_filter)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not create collage';
        logErrorAndDie($errormsg);
    }
}

foreach ($srcImages as $image) {
    $filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;
    $filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $image;
    $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $image;
    $filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;

    if (!file_exists($filename_tmp)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $filename_tmp . ' does not exist';
        logErrorAndDie($errormsg);
    }

    $imageResource = imagecreatefromjpeg($filename_tmp);

    if ($_POST['style'] === 'collage' && $file != $image) {
        $editSingleCollage = true;
        $picture_frame = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $config['collage']['frame']);
    } else {
        $editSingleCollage = false;
        $picture_frame = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $config['picture']['frame']);
    }

    if ($_POST['style'] !== 'collage' || $editSingleCollage) {
        // Only jpg/jpeg are supported
        if (!$imageResource) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not read jpeg file. Are you taking raws?';
            logErrorAndDie($errormsg);
        }

        if ($config['picture']['flip'] !== 'off') {
            if ($config['picture']['flip'] === 'horizontal') {
                imageflip($imageResource, IMG_FLIP_HORIZONTAL);
            } elseif ($config['picture']['flip'] === 'vertical') {
                imageflip($imageResource, IMG_FLIP_VERTICAL);
            } elseif ($config['picture']['flip'] === 'both') {
                imageflip($imageResource, IMG_FLIP_BOTH);
            }
            $imageModified = true;
        }

        // apply filter
        if ($image_filter) {
            applyFilter($image_filter, $imageResource);
            $imageModified = true;
        }

        if ($config['picture']['polaroid_effect'] && $_POST['style'] !== 'collage') {
            $polaroid_rotation = $config['picture']['polaroid_rotation'];
            $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
            $imageModified = true;
        }

        if (
            ($config['picture']['take_frame'] && $_POST['style'] !== 'collage' && testFile($config['picture']['frame'])) ||
            ($editSingleCollage && $config['collage']['take_frame'] === 'always' && testFile($config['collage']['frame']))
        ) {
            $frame = imagecreatefrompng($picture_frame);
            $frame = resizePngImage($frame, imagesx($imageResource), imagesy($imageResource));
            $x = imagesx($imageResource) / 2 - imagesx($frame) / 2;
            $y = imagesy($imageResource) / 2 - imagesy($frame) / 2;
            imagecopy($imageResource, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
            $imageModified = true;
        }

        if ($config['picture']['rotation'] !== '0') {
            $imageResource = rotateResizeImage($imageResource, $config['picture']['rotation']);
            $imageModified = true;
        }
    }

    if ($config['keying']['enabled'] || $_POST['style'] === 'chroma') {
        $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
        imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality']['chroma']);
        imagedestroy($chromaCopyResource);
    }

    if ($config['textonpicture']['enabled'] && testFile($config['textonpicture']['font']) && $_POST['style'] !== 'collage') {
        imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
        imagedestroy($imageResource);
        ApplyText($filename_photo, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolor, $fontpath, $line1text, $line2text, $line3text, $linespacing);
        $imageModified = true;
        $imageResource = imagecreatefromjpeg($filename_photo);
    }

    // image scale, create thumbnail
    $thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

    imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb']);
    imagedestroy($thumbResource);

    if ($imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
        imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
        // preserve jpeg meta data
        if ($config['picture']['preserve_exif_data'] && $config['exiftool']['cmd']) {
            $cmd = sprintf($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
            $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

            exec($cmd, $output, $returnValue);

            if ($returnValue) {
                $ErrorData = [
                    'error' => 'exiftool returned with an error code',
                    'cmd' => $cmd,
                    'returnValue' => $returnValue,
                    'output' => $output,
                ];
                $ErrorString = json_encode($ErrorData);
                logError($ErrorData);
                die($ErrorString);
            }
        }
    } else {
        copy($filename_tmp, $filename_photo);
    }

    if (!$config['picture']['keep_original']) {
        unlink($filename_tmp);
    }

    imagedestroy($imageResource);

    // insert into database
    if ($config['database']['enabled']) {
        if ($_POST['style'] !== 'chroma' || ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === true)) {
            appendImageToDB($image);
        }
    }

    // Change permissions
    chmod($filename_photo, octdec($picture_permissions));
}

if ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === false) {
    unlink($filename_photo);
    unlink($filename_thumb);
}

$LogData = [
    'file' => $file,
    'images' => $srcImages,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['enabled'] && $config['dev']['advanced_log']) {
    logError($LogData);
}
echo $LogString;
