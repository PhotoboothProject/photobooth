<?php
header('Content-Type: application/json');

if (!isset($_POST['imgData']) || empty($_POST['imgData'])) {
    http_response_code(400);
    $logData = [
        'success' => false,
        'error' => 'imgData not set or empty.',
    ];
    $logString = json_encode($logData);
    die($logString);
}

require_once '../../lib/config.php';
require_once '../../lib/db.php';
require_once '../../lib/image.php';
require_once '../../lib/resize.php';

$file = Image::create_new_filename($config['picture']['naming']);

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

if ($config['database']['file'] != 'db') {
    $file = $config['database']['file'] . '_' . $file;
}

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);

try {
    $img = $_POST['imgData'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $imageResource = imagecreatefromstring($data);
    if (!$imageResource) {
        throw new Exception('Failed to create image from data.');
    }
    if (!imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image'])) {
        throw new Exception('Failed to save ' . $filename_photo);
    }
    if (!copy($filename_photo, $filename_keying)) {
        throw new Exception('Failed to save ' . $filename_keying);
    }

    // image scale, create thumbnail
    $thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);
    if (!$thumbResource) {
        throw new Exception('Failed to resize thumbnail.');
    }
    if (!imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb'])) {
        throw new Exception('Failed to save thumbnail.');
    }

    // clear cache
    imagedestroy($thumbResource);
    imagedestroy($imageResource);

    // insert into database
    if ($config['database']['enabled']) {
        if (!$database->appendContentToDB($file)) {
            throw new Exception('Failed to add ' . $file . ' to database.');
        }
    }

    // Change permissions
    if (!chmod($filename_photo, octdec($picture_permissions))) {
        throw new Exception('Failed to change file permissions.');
    }
} catch (Exception $e) {
    // Try to clear cache
    if (is_resource($thumbResource)) {
        imagedestroy($thumbResource);
    }
    if (is_resource($imageResource)) {
        imagedestroy($imageResource);
    }
    $logData = [
        'success' => false,
        'error' => $e->getMessage(),
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $logString = json_encode($logData);
    die($logString);
}

// send imagename to frontend
$logData = [
    'success' => true,
    'filename' => $file,
    'php' => basename($_SERVER['PHP_SELF']),
];
$logString = json_encode($logData);
die($logString);
