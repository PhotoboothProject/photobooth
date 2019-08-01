<?php
require_once('../../config.inc.php');

$file = md5(time()).'.jpg';

$uniqid = uniqid();
$filename_photo = '../../'.$config['folders']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = '../../'.$config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $file;

// get data from data.txt
if(!file_exists('../../data.txt')){
	file_put_contents('../../data.txt', json_encode(array()));
}
$images = json_decode(file_get_contents('../../data.txt'));


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
file_put_contents('../../data.txt', json_encode($images));

// send imagename to frontend
echo json_encode(array('success' => true, 'img' => $file));
?>