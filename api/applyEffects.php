<?php
header('Content-Type: application/json');

require_once('../lib/db.php');
require_once('../lib/config.php');
require_once('../lib/filter.php');
require_once('../lib/polaroid.php');
require_once('../lib/resize.php');
require_once('../lib/collage.php');

$time = [];

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

    if (!createCollage($collageSrcImagePaths, $filename_tmp)) {
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

$time_start = microtime(true);
// apply filter
if ($image_filter) {
    applyFilter($image_filter, $imageResource);
}
$time['filter'] = microtime(true) - $time_start;
$time_start = microtime(true);

if ($config['polaroid_effect']) {
    $polaroid_rotation = $config['polaroid_rotation'];

    $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
}
$time['polaroid'] = microtime(true) - $time_start;
$time_start = microtime(true);

if ($config['chroma_keying']) {
    $chromaCopyResource = resizeImage($imageResource, 1500, 1000);

    imagejpeg($chromaCopyResource, $filename_keying, 70);
    imagedestroy($chromaCopyResource);
}
$time['chroma'] = microtime(true) - $time_start;
$time_start = microtime(true);

// image scale, create thumbnail
$thumbResource = resizeImage($imageResource, 500, 500);

imagejpeg($thumbResource, $filename_thumb, 60);
imagedestroy($thumbResource);
$time['thumb'] = microtime(true) - $time_start;
$time_start = microtime(true);

imagejpeg($imageResource, $filename_photo, 80);
imagedestroy($imageResource);
$time['image'] = microtime(true) - $time_start;
$time_start = microtime(true);

// insert into database
appendImageToDB($file);

sleep(5);

echo json_encode([
    'file' => $file,
    'time' => $time,
]);