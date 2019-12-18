<?php
header('Content-Type: application/json');

require_once('../lib/db.php');
require_once('../lib/config.php');
require_once('../lib/filter.php');
require_once('../lib/polaroid.php');
require_once('../lib/resize.php');
require_once('../lib/collage.php');

if (empty($_POST['file']) || !preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file'])) {
    die(json_encode([
        'error' => 'Invalid file provided',
    ]));
}

$file = $_POST['file'];

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

if (isset($_POST['isCollage']) && $_POST['isCollage'] === 'true') {
    $collageBasename = substr($filename_tmp, 0, -4);
    $collageSrcImagePaths = [];

    for ($i = 0; $i < 4; $i++) {
        $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
    }

    if (!createCollage($collageSrcImagePaths, $filename_tmp, $config['take_frame'], $config['take_frame_path'])) {
        die(json_encode([
            'error' => 'Could not create collage'
        ]));
    }
}

if (!file_exists($filename_tmp)) {
    die(json_encode([
        'error' => 'File does not exist'
    ]));
}

$imageResource = imagecreatefromjpeg($filename_tmp);

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

if (!empty($_POST['filter']) && $_POST['filter'] !== 'imgPlain') {
    $image_filter = $_POST['filter'];
}

// apply filter
if ($image_filter) {
    applyFilter($image_filter, $imageResource);
}

if ($config['polaroid_effect']) {
    $polaroid_rotation = $config['polaroid_rotation'];

    $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
}

if ($config['take_frame'] && $_POST['isCollage'] !== 'true') {
    $frame = imagecreatefrompng($config['take_frame_path']);
    $frame = resizePngImage($frame, imagesx($imageResource), imagesy($imageResource));
    $x = (imagesx($imageResource)/2) - (imagesx($frame)/2);
    $y = (imagesy($imageResource)/2) - (imagesy($frame)/2);
    imagecopy($imageResource, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
}

if ($config['chroma_keying']) {
    $chromaCopyResource = resizeImage($imageResource, 1500, 1000);

    imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality_chroma']);
    imagedestroy($chromaCopyResource);
}

// image scale, create thumbnail
$thumbResource = resizeImage($imageResource, 500, 500);

imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality_thumb']);
imagedestroy($thumbResource);

imagejpeg($imageResource, $filename_photo, $config['jpeg_quality_image']);
imagedestroy($imageResource);

// insert into database
appendImageToDB($file);

echo json_encode([
    'file' => $file,
]);
