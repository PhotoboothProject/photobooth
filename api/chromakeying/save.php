<?php
header('Content-Type: application/json');

require_once('../../lib/config.php');

if($config['file_format_date'] == true) {
	$file = date('Ymd_His').'.jpg';
} else {
	$file = md5(time()).'.jpg';
}

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

// get data from db.txt
if(!file_exists('../../data/db.txt')){
	$images = [];
} else {
	$images = json_decode(file_get_contents('../../data/db.txt'));
}


$img = $_POST['imgData'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$image = imagecreatefromstring($data);
imagejpeg($image, $filename_photo, 100);

$image = ResizeJpgImage($image, 500, 500);
imagejpeg($image, $filename_thumb, 100);

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
$images[] = $file;
file_put_contents('../../data/db.txt', json_encode($images));

// send imagename to frontend
echo json_encode(array('success' => true, 'filename' => $file));
?>