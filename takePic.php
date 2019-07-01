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
$filename_tmp = $config['folders']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $file;

if($config['use_filter'] === false) {
	$filename_orig = $filename_photo;
} else {
	$filename_orig = $filename_tmp;
}

if($config['dev'] === false) {
	$shootimage = shell_exec(
		sprintf(
			$config['take_picture']['cmd'],
			$filename_orig
			)
		);
		if(strpos($shootimage, $config['take_picture']['msg']) === false) {
			die(json_encode(array('error' => true)));
		}
} else {
	$devImg = array('resources/img/bg.jpg');
	copy(
		$devImg[array_rand($devImg)],
		$filename_orig
	);
}

// apply filter
if($config['use_filter'] === true) {
	$tmp = imagecreatefromjpeg($filename_orig);

	switch($config['imgfilter_filter_mode']) {
		case 'grayscale':
			imagefilter($tmp, IMG_FILTER_GRAYSCALE);
			break;
		case 'negate':
			imagefilter($tmp, IMG_FILTER_NEGATE);
			break;
		case 'sepia_dark':
			imagefilter($tmp, IMG_FILTER_GRAYSCALE);
			imagefilter($tmp, IMG_FILTER_BRIGHTNESS,-30);
			imagefilter($tmp, IMG_FILTER_COLORIZE, 90, 55, 30);
			break;
		case 'sepia_light':
			imagefilter($tmp, IMG_FILTER_GRAYSCALE);
			imagefilter($tmp, IMG_FILTER_COLORIZE, 90, 60, 40);
			break;
		default:
			break;
	}
	imagejpeg($tmp, $filename_photo);
	imagedestroy($tmp);
}

// image scale, create thumbnail
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
