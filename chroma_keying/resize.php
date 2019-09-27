<?php
require_once('../lib/config.php');

$sourcePath = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR .$_POST['filename'];
$targetPath = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR .$_POST['filename'];

$source = imagecreatefromjpeg($sourcePath);
$source = ResizeJpgImage($source, 1500, 1000);
imagejpeg($source, $targetPath, 100);
imagedestroy($source);

echo json_encode(array('targetPath' => $targetPath));

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
?>
