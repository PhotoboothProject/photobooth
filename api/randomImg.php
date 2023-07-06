<?php
/****************************************************
			RANDOM FRAME/BACKGROUND/... 
	This "script" allows to randomize images,
	backgrounds, canvas, frames, etc. so 
	pictures taken are "funnier" and an element
	of "surprise".
			
*****************************************************/

require_once '../lib/config.php';

if (isset($_GET['dir']) && !empty($_GET['dir'])) {
    $dir = $_GET['dir'];
} else {
    $dir = 'demoframes';
}

$path = $config['foldersAbs']['private'] . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $dir;

if ($dir == 'demoframes' || !is_dir($path)) {
    $path = realpath('../resources/img/frames');
}

$files = array_diff(scandir($path), ['.', '..']);

/* - - - - - - - - - - */

$images = array_rand($files);
$image = $files[$images];
$filename = $path . DIRECTORY_SEPARATOR . basename($image);
$file_extension = strtolower(substr(strrchr($filename, '.'), 1));

switch ($file_extension) {
    case 'gif':
        $ctype = 'image/gif';
        break;
    case 'png':
        $ctype = 'image/png';
        break;
    case 'jpeg':
    case 'jpg':
        $ctype = 'image/jpeg';
        break;
    case 'svg':
        $ctype = 'image/svg+xml';
        break;
    default:
}

header('Content-type: ' . $ctype);
readfile($filename);
?>
