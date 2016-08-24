<?php
require('db.php');
$folder = 'images/';
$file = md5(time()).'.jpg';
$filename = $folder.$file;
$shootimage = shell_exec('sudo gphoto2 --capture-image-and-download --filename='.$filename.' images');


if(strpos($shootimage, 'New file is in location') === false) {
	echo json_encode(array('error' => true));	
} else {
	// Scale with avconv
	$scaleimage = shell_exec('avconv -i '.$filename.' -vf scale=500:-1 thumbs/'.$file);
	
	// Insert into DB file
	$data[] = $file;
	file_put_contents('data.txt', implode(PHP_EOL,$data));
	
	// Echo Imagename for Result Page
	echo json_encode(array('success' => true, 'img' => $file));
}
