<?php

require_once('db.php');
require_once('config.inc.php');

$file = md5(time()).'.jpg';
$filename_photo = $config['folders']['images'].'/'.$file;
$filename_thumb = $config['folders']['thumbs'].'/'.$file;

$shootimage = shell_exec(
    sprintf(
        $config['take_picture'][$config['os']]['cmd'],
        $filename_photo
    )
);

if(strpos($shootimage, $config['take_picture'][$config['os']]['msg']) === false) {
	echo json_encode(array('error' => true));	
} else {
    // image scale
    list($width, $height) = getimagesize($filename_photo);
    $newwidth = 500;
    $newheight = $height * (1 / $width * 500);
    $source = imagecreatefromjpeg($filename_photo);
    $thumb = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
    imagejpeg($thumb, $filename_thumb);
    
	// insert into database
	$images[] = $file;
	file_put_contents('data.txt', json_encode($images));
	
	// send imagename to frontend
	echo json_encode(array('success' => true, 'img' => $file));
}
