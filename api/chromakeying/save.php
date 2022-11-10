<?php
header('Content-Type: application/json');

require_once '../../lib/config.php';
require_once '../../lib/db.php';
require_once '../../lib/resize.php';

$name = Image::create_new_filename($config['picture']['naming']);

if ($config['database']['file'] === 'db') {
    $file = $name;
} else {
    $file = $config['database']['file'] . '_' . $name;
}

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);

$img = $_POST['imgData'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$imageResource = imagecreatefromstring($data);
imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
copy($filename_photo, $filename_keying);

// image scale, create thumbnail
$thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);
imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb']);
imagedestroy($thumbResource);

imagedestroy($imageResource);

// insert into database
if ($config['database']['enabled']) {
    appendImageToDB($file);
}

// Change permissions
chmod($filename_photo, octdec($picture_permissions));

// send imagename to frontend
echo json_encode([
    'success' => true,
    'filename' => $file,
]);
