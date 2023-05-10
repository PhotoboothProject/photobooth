<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/collage.php';
require_once '../lib/applyEffects.php';
require_once '../lib/image.php';
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

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

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

$isCollage = false;
if ($_POST['style'] === 'collage') {
    $isCollage = true;
}

$srcImages = [];
$srcImages[] = $file;

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

if ($isCollage) {
    list($collageSrcImagePaths, $srcImages) = getCollageFiles($config['collage'], $filename_tmp, $file, $srcImages);

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

    $imageHandler = new Image();
    $imageHandler->imageModified = false;

    $imageResource = $imageHandler->createFromImage($filename_tmp);
    if (!$imageResource) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Error here';
        logErrorAndDie($errormsg);
    }

    if ($isCollage && $file != $image) {
        $editSingleCollage = true;
        $imageHandler->framePath = $config['collage']['frame'];
    } else {
        $editSingleCollage = false;
        $imageHandler->framePath = $config['picture']['frame'];
    }

    if (!$isCollage || $editSingleCollage) {
        if ($config['picture']['flip'] !== 'off') {
            if ($config['picture']['flip'] === 'horizontal') {
                imageflip($imageResource, IMG_FLIP_HORIZONTAL);
            } elseif ($config['picture']['flip'] === 'vertical') {
                imageflip($imageResource, IMG_FLIP_VERTICAL);
            } elseif ($config['picture']['flip'] === 'both') {
                imageflip($imageResource, IMG_FLIP_BOTH);
            }

            $imageHandler->imageModified = true;
        }

        // apply filter
        if ($image_filter) {
            applyFilter($image_filter, $imageResource);
            $imageHandler->imageModified = true;
        }

        if ($config['picture']['polaroid_effect'] && !$isCollage) {
            $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
            $imageResource = $imageHandler->effectPolaroid($imageResource);
        }

        if (($config['picture']['take_frame'] && !$isCollage) || ($editSingleCollage && $config['collage']['take_frame'] === 'always')) {
            if (!$isCollage) {
                $imageHandler->frameExtend = $config['picture']['extend_by_frame'];
            } else {
                $imageHandler->frameExtend = false;
            }
            $imageResource = $imageHandler->applyFrame($imageResource);
        }

        if ($config['picture']['rotation'] !== '0') {
            $imageHandler->resizeRotation = $config['picture']['rotation'];
            $imageResource = $imageHandler->rotateResizeImage($imageResource);
        }
    }
    if ($config['keying']['enabled'] || $_POST['style'] === 'chroma') {
        $chroma_size = substr($config['keying']['size'], 0, -2);
        $imageHandler->resizeMaxWidth = $chroma_size;
        $imageHandler->resizeMaxHeight = $chroma_size;
        $chromaCopyResource = $imageHandler->resizeImage($imageResource);
        $imageHandler->jpegQuality = $config['jpeg_quality']['chroma'];
        $imageHandler->saveJpeg($chromaCopyResource, $filename_keying);
        imagedestroy($chromaCopyResource);
    }

    if (!$isCollage && $config['textonpicture']['enabled']) {
        $imageHandler->fontSize = $config['textonpicture']['font_size'];
        $imageHandler->fontRotation = $config['textonpicture']['rotation'];
        $imageHandler->fontLocationX = $config['textonpicture']['locationx'];
        $imageHandler->fontLocationY = $config['textonpicture']['locationy'];
        $imageHandler->fontColor = $config['textonpicture']['font_color'];
        $imageHandler->fontPath = $config['textonpicture']['font'];
        $imageHandler->textLine1 = $config['textonpicture']['line1'];
        $imageHandler->textLine1 = $config['textonpicture']['line2'];
        $imageHandler->textLine3 = $config['textonpicture']['line3'];
        $imageHandler->textLineSpacing = $config['textonpicture']['linespace'];
        $imageResource = $imageHandler->applyText($imageResource);
    }

    // image scale, create thumbnail
    $thumb_size = substr($config['picture']['thumb_size'], 0, -2);
    $imageHandler->resizeMaxWidth = $thumb_size;
    $imageHandler->resizeMaxHeight = $thumb_size;
    $thumbResource = $imageHandler->resizeImage($imageResource);

    $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
    $imageHandler->saveJpeg($thumbResource, $filename_thumb);
    imagedestroy($thumbResource);

    $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
    if ($imageHandler->imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
        $imageHandler->saveJpeg($imageResource, $filename_photo);
        // preserve jpeg meta data
        if ($config['picture']['preserve_exif_data'] && $config['exiftool']['cmd']) {
            addExifData($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
        }
    } else {
        copy($filename_tmp, $filename_photo);
    }
    imagedestroy($imageResource);

    if (!$config['picture']['keep_original']) {
        unlink($filename_tmp);
    }

    // insert into database
    if ($config['database']['enabled']) {
        if ($_POST['style'] !== 'chroma' || ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === true)) {
            $database->appendContentToDB($image);
        }
    }

    // Change permissions
    $picture_permissions = $config['picture']['permissions'];
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
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
echo $LogString;
