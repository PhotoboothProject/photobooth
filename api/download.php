<?php
require_once('../lib/config.php');

$image = (isset($_GET['image']) && $_GET['image']) != '' ? $_GET['image'] : false;
if($image) {
	$filename_source = $config['folders']['images'] . DIRECTORY_SEPARATOR . $image;

	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="photobooth-'.$image.'"');
	echo file_get_contents(__DIR__.'/../'.$filename_source);
	exit;
}
