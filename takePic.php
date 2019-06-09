<?php

require_once('db.php');
require_once('folders.php');

$my_config = 'my.config.inc.php';
if (file_exists($my_config)) {
	require_once('my.config.inc.php');
} else {
	require_once('config.inc.php');
}

if($config['file_format_date'] == true) {
	$file = date('Ymd_His').'.jpg';
} else {
	$file = md5(time()).'.jpg';
}

$filename_photo = $config['folders']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $file;

if($config['dev'] === false) {
	$shootimage = shell_exec(
		sprintf(
			$config['take_picture']['cmd'],
			$filename_photo
			)
		);
		if(strpos($shootimage, $config['take_picture']['msg']) === false) {
			die(json_encode(array('error' => true)));
		}
} else {
	$devImg = array('resources/img/bg.jpg');
	copy(
		$devImg[array_rand($devImg)],
		$filename_photo
	);
}

// image scale
list($width, $height) = getimagesize($filename_photo);
$newwidth = 500;
$newheight = $height * (1 / $width * 500);
$source = imagecreatefromjpeg($filename_photo);
$thumb = imagecreatetruecolor($newwidth, $newheight);
imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
imagejpeg($thumb, $filename_thumb);
imagedestroy($source);
imagedestroy($thumb);

// insert into database
$images[] = $file;
file_put_contents('data.txt', json_encode($images));

// send imagename to frontend
echo json_encode(array('success' => true, 'img' => $file));
