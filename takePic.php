<?php

require_once('db.php');
require_once('config.inc.php');

$file = md5(time()).'.jpg';
$filename_photo = $config['folders']['images'].'/'.$file;
$filename_thumb = $config['folders']['thumbs'].'/'.$file;

$shootimage = shell_exec('sudo gphoto2 --capture-image-and-download --filename='.$filename_photo.' images');

if(strpos($shootimage, 'New file is in location') === false) {
	echo json_encode(array('error' => true));	
} else {
	// Scale with avconv
	$scaleimage = shell_exec('avconv -i '.$filename_photo.' -vf scale=500:-1 '.$filename_thumb);
	
	// Insert into DB file
	$images[] = $file;
	file_put_contents('data.txt', json_encode($images));
	
	// Echo Imagename for Result Page
	echo json_encode(array('success' => true, 'img' => $file));
}
