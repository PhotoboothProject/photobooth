<?php 

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if($image) {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="photobooth-'.$image.'.jpg"');
	echo file_get_contents(__DIR__.'/images/'.$image);
	exit;
}
