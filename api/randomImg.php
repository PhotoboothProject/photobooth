<?php
/****************************************************
			RANDOM FRAME/BACKGROUND/... 
	This "script" allows to randomize images,
	backgrounds, canvas, frames, etc. so 
	pictures taken are "funnier" and an element
	of "surprise".
	
	USE :
	
	LINUX :
	sudo chmod -R 777 private

	LINUX AND WINDOWS:
	add an /images/ (0777) folder under /private/
	
	For hassle-free (ssh/sftp-free) upload, you may want to use the uploader : http://127.0.0.1/admin/upload/
	
	 (in /admin or my.config.inc.php)
		I - PICTURE FRAMES 
			1/Copy all the (transparent) frames you want to private/images/{FrameFolder}
			2/Enable picture_take_frame
			3/specify picture_frame url : http://127.0.0.1/api/randomImg.php?dir={FrameFolder}
			
		II - COLLAGE FRAMES & BACKGROUND
			FOR FRAMES
			NOTE : you can specify a diffrent {FrameFolder} for collage frames if needed
			1/Copy all the (transparent) frames you want to private/images/{FrameFolder}
			2/Enable collage_take_frame (always or once)
			3/specify collage_frame url : http://127.0.0.1/api/randomImg.php?dir={FrameFolder}
			
			FOR BACKGROUNDS
			1/Copy all the backgrounds you want to private/images/{BgFolder}
			2/specify collage_background url : http://127.0.0.1/api/randomImg.php?dir={BgFolder}
			
			NOTE: Same thing can be applied for collage_placeholderpath so a random holder image takes place.
			
*****************************************************/

require_once '../lib/config.php';

$dir = $_GET['dir'];

if ($dir == 'demoframes') {
    $path = realpath('../resources/img/frames');
} else {
    $path = $config['foldersAbs']['private'] . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $dir;
}

$files = scandir($path);
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
