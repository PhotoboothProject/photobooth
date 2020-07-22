<?php
header('Content-Type: application/json');

require_once('../../lib/config.php');
require_once('../../lib/db.php');

if ($config['file_naming'] === 'numbered') {
    $images = getImagesFromDB();
    $img_number = count($images);
    $files = str_pad(++$img_number, 4, '0', STR_PAD_LEFT);
    $name = $files.'.jpg';
} elseif ($config['file_naming'] === 'dateformatted') {
    $name = date('Ymd_His').'.jpg';
} else {
    $name = md5(time()).'.jpg';
}

if ($config['db_file'] === 'db') {
    $file = $name;
} else {
    $file = $config['db_file'].'_'.$name;
}

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

$img = $_POST['imgData'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$image = imagecreatefromstring($data);
imagejpeg($image, $filename_photo, $config['jpeg_quality_image']);

$image = ResizeJpgImage($image, 500, 500);
imagejpeg($image, $filename_thumb, $config['jpeg_quality_thumb']);

imagedestroy($image);

function ResizeJpgImage($image, $max_width, $max_height)
{
	$old_width  = imagesx($image);
	$old_height = imagesy($image);
	$scale      = min($max_width/$old_width, $max_height/$old_height);
	$new_width  = ceil($scale*$old_width);
	$new_height = ceil($scale*$old_height);
	$new = imagecreatetruecolor($new_width, $new_height);
	imagecopyresampled($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
	return $new;
}

// insert into database
appendImageToDB($file);

// Change permissions
chmod($filename_photo, octdec($picture_permissions));

// send imagename to frontend
echo json_encode(array('success' => true, 'filename' => $file));
?>
