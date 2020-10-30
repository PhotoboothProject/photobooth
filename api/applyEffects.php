<?php
header('Content-Type: application/json');

require_once('../lib/db.php');
require_once('../lib/config.php');
require_once('../lib/filter.php');
require_once('../lib/polaroid.php');
require_once('../lib/resize.php');
require_once('../lib/collage.php');

if (!extension_loaded('gd')) {
    die(json_encode([
        'error' => 'GD library not loaded! Please enable GD!',
    ]));
}

if (empty($_POST['file'])) {
    die(json_encode([
        'error' => 'No file provided',
    ]));
}

$file = $_POST['file'];

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$frame_path = __DIR__ . DIRECTORY_SEPARATOR .$config['take_frame_path'];
$collage_frame_path = __DIR__ . DIRECTORY_SEPARATOR .$config['take_collage_frame_path'];
$collage_background = __DIR__ . DIRECTORY_SEPARATOR .$config['collage_background'];
$picture_permissions = $config['picture_permissions'];
$thumb_size = substr($config['thumb_size'], 0, -2);
$chroma_size = substr($config['chroma_size'], 0, -2);

if (isset($_POST['isCollage']) && $_POST['isCollage'] === 'true') {
    $collageBasename = substr($filename_tmp, 0, -4);
    $collageSrcImagePaths = [];

    for ($i = 0; $i < 4; $i++) {
        $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
    }

    if (!createCollage($collageSrcImagePaths, $filename_tmp, $config['take_collage_frame'], $config['take_collage_frame_always'], $collage_frame_path, $collage_background)) {
        die(json_encode([
            'error' => 'Could not create collage'
        ]));
    }

    if (!$config['keep_images']) {
        foreach ($collageSrcImagePaths as $tmp) {
             unlink($tmp);
        }
    }
}

if (!file_exists($filename_tmp)) {
    die(json_encode([
        'error' => 'File does not exist'
    ]));
}

// Only jpg/jpeg are supported
$imginfo = getimagesize($filename_tmp);
$mimetype = $imginfo['mime'];
if ($mimetype != 'image/jpg' && $mimetype != 'image/jpeg') {
    die(json_encode([
        'error' => 'The source file type ' . $mimetype . ' is not supported'
    ]));
}

$imageResource = imagecreatefromjpeg($filename_tmp);
$imageModified = false;

if (!$imageResource) {
    die(json_encode([
        'error' => 'Could not read jpeg file. Are you taking raws?',
    ]));
}

if (!isset($_POST['filter'])) {
    die(json_encode([
        'error' => 'No filter provided'
    ]));
}

$image_filter = false;

if (!empty($_POST['filter']) && $_POST['filter'] !== 'plain') {
    $image_filter = $_POST['filter'];
}

// apply filter
if ($image_filter) {
    applyFilter($image_filter, $imageResource);
    $imageModified = true;
}

if ($config['pictureRotation'] !== '0') {
    $rotatedImg = imagerotate($imageResource, $config['pictureRotation'], 0);
    $imageResource = $rotatedImg;
    $imageModified = true;
}

if ($config['polaroid_effect']) {
    $polaroid_rotation = $config['polaroid_rotation'];
    $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
    $imageModified = true;
}

if ($config['take_frame'] && $_POST['isCollage'] !== 'true') {
    $frame = imagecreatefrompng($frame_path);
    $frame = resizePngImage($frame, imagesx($imageResource), imagesy($imageResource));
    $x = (imagesx($imageResource)/2) - (imagesx($frame)/2);
    $y = (imagesy($imageResource)/2) - (imagesy($frame)/2);
    imagecopy($imageResource, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
    $imageModified = true;
}

if ($config['chroma_keying']) {
    $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
    imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality_chroma']);
    imagedestroy($chromaCopyResource);
}

// image scale, create thumbnail
$thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality_thumb']);
imagedestroy($thumbResource);

if ($imageModified || $config['jpeg_quality_image'] >= 0 && $config['jpeg_quality_image'] < 100) {
    imagejpeg($imageResource, $filename_photo, $config['jpeg_quality_image']);
    // preserve jpeg meta data
    if ($config['preserve_exif_data'] && $config['exiftool']['cmd']) {
        $cmd = sprintf($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
        exec($cmd, $output, $returnValue);
        if ($returnValue) {
            die(json_encode([
                'error' => 'exiftool returned with an error code',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ]));
        }
    }
} else {
    copy($filename_tmp, $filename_photo);
}

if (!$config['keep_images']) {
    unlink($filename_tmp);
}

imagedestroy($imageResource);

// insert into database
appendImageToDB($file);

// Change permissions
chmod($filename_photo, octdec($picture_permissions));

echo json_encode([
    'file' => $file,
]);
