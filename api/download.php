<?php
require_once('../lib/config.php');

$download_thumbs = $config['download_thumbs'];
$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if($image) {

	$fullres = $config['folders']['images'] . DIRECTORY_SEPARATOR . $image;
	$thumbres = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $image;

	if ($download_thumbs) {
		$filename_source = $thumbres;
	} else {
		$filename_source = $fullres;
	}

	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="photobooth-'.$image.'"');
	echo file_get_contents(__DIR__.'/../'.$filename_source);
	exit;
}
