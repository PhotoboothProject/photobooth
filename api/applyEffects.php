<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/polaroid.php';
require_once '../lib/resize.php';
require_once '../lib/collage.php';
require_once '../lib/applyText.php';
require_once '../lib/applyEffects.php';
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

if ($_POST['style'] === 'collage') {
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

    $imageResource = imagecreatefromjpeg($filename_tmp);

    if ($_POST['style'] === 'collage' && $file != $image) {
        $editSingleCollage = true;
        $picture_frame = $config['collage']['frame'];
    } else {
        $editSingleCollage = false;
        $picture_frame = $config['picture']['frame'];
    }

    if ($_POST['style'] !== 'collage' || $editSingleCollage) {
        list($imageResource, $imageModified) = editSingleImage($config, $imageResource, $image_filter, $editSingleCollage, $picture_frame, $_POST['style'] == 'collage');
    }

    if ($config['keying']['enabled'] || $_POST['style'] === 'chroma') {
        $chroma_size = substr($config['keying']['size'], 0, -2);
        $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
        imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality']['chroma']);
        imagedestroy($chromaCopyResource);
    }

    $configText = $config['textonpicture'];
    list($imageResource, $imageModified) = addTextToImage($configText, $imageResource, $imageModified, $_POST['style'] == 'collage');

    // image scale, create thumbnail
    $thumb_size = substr($config['picture']['thumb_size'], 0, -2);
    $thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

    imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb']);
    imagedestroy($thumbResource);

    compressImage($config, $imageModified, $imageResource, $filename_tmp, $filename_photo);

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
