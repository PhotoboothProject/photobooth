<?php

$my_config = 'my.config.inc.php';
if (file_exists($my_config)) {
	require_once('my.config.inc.php');
} else {
	require_once('config.inc.php');
}
require_once('db.php');
require_once('folders.php');

$filename = trim(basename($_GET['filename']));
if($pos = strpos($filename, '?')) {
    $parts = explode('?', $filename);
    $filename = array_shift($parts);
}

$filename_source = $config['folders']['images'] . DIRECTORY_SEPARATOR . $filename;
$filename_print = $config['folders']['print'] . DIRECTORY_SEPARATOR . $filename;
$filename_codes = $config['folders']['qrcodes'] . DIRECTORY_SEPARATOR . $filename;
$filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $filename;
$status = false;

// text on print variables
$fontsize = $config['fontsize'];
$fontlocx = $config['locationx'];
$fontlocy = $config['locationy'];
$linespacing = $config['linespace'];
$fontrot = $config['rotation'];
$line1text = $config['textonprint']['line1'];
$line2text = $config['textonprint']['line2'];
$line3text = $config['textonprint']['line3'];

// exit with error
if(!file_exists($filename_source)) {
    echo json_encode(array('status' => sprintf('file "%s" not found', $filename_source)));
} else {
    // print
    // copy and merge
    if(!file_exists($filename_print)) {
        if($config['print_qrcode'] == true) {
            // create qr code
            if(!file_exists($filename_codes)) {
                include('resources/lib/phpqrcode/qrlib.php');
                $url = 'http://'.$_SERVER['HTTP_HOST'].'/download.php?image=';
                QRcode::png($url.$filename, $filename_codes, QR_ECLEVEL_H, 10);
            }

            // merge source and code
            list($width, $height) = getimagesize($filename_source);
            $newwidth = $width + ($height / 2);
            $newheight = $height;

            $source = imagecreatefromjpeg($filename_source);
            $code = imagecreatefrompng($filename_codes);

            if($config['print_frame'] == true) {
                $print = imagecreatefromjpeg($filename_source);
                $rahmen = @imagecreatefrompng('resources/img/frames/frame.png');
                $rahmen = ResizePngImage($rahmen, imagesx($print), imagesy($print));
                $x = (imagesx($print)/2) - (imagesx($rahmen)/2);
                $y = (imagesy($print)/2) - (imagesy($rahmen)/2);
                imagecopy($print, $rahmen, $x, $y, 0, 0, imagesx($rahmen), imagesy($rahmen));
                imagejpeg($print, $filename_print);
                imagedestroy($print);
                // $source needs to be redefined, picture with frame now exists inside $filename_print
                imagedestroy($source);
                $source = imagecreatefromjpeg($filename_print);
            }

            $print = imagecreatetruecolor($newwidth, $newheight);

            imagefill($print, 0, 0, imagecolorallocate($print, 255, 255, 255));
            imagecopy($print, $source , 0, 0, 0, 0, $width, $height);
            imagecopyresized($print, $code, $width, 0, 0, 0, ($height / 2), ($height / 2), imagesx($code), imagesy($code));

            // text on image - start  - IMPORTANT  ensure you download Google Great Vibes font
            if($config['is_textonprint'] == true) {
                $fontcolour = imagecolorallocate($print, 0, 0, 0);  // colour of font
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line1text);
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line2text);
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy + ($linespacing *2), $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line3text);
            }
            // text on image - end

            imagejpeg($print, $filename_print);
            imagedestroy($code);
            imagedestroy($source);
        } else {
            $print = imagecreatefromjpeg($filename_source);
            if($config['print_frame'] == true) {
                $rahmen = @imagecreatefrompng('resources/img/frames/frame.png');
                $rahmen = ResizePngImage($rahmen, imagesx($print), imagesy($print));
                $x = (imagesx($print)/2) - (imagesx($rahmen)/2);
                $y = (imagesy($print)/2) - (imagesy($rahmen)/2);
                imagecopy($print, $rahmen, $x, $y, 0, 0, imagesx($rahmen), imagesy($rahmen));
            }
            // text on image - start  - IMPORTANT  ensure you download Google Great Vibes font
            if($config['is_textonprint'] == true) {
                $fontcolour = imagecolorallocate($print, 0, 0, 0);  // colour of font
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line1text);
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line2text);
                imagettftext ($print, $fontsize, $fontrot, $fontlocx, $fontlocy + ($linespacing *2), $fontcolour, 'resources/fonts/GreatVibes-Regular.ttf' , $line3text);
            }
            //text on image - end
            imagejpeg($print, $filename_print);
        }
        imagedestroy($print);
    }

    // print image
    // fixme: move the command to the config.inc.php
    $printimage = shell_exec(
        sprintf(
            $config['print']['cmd'],
            $filename_print
        )
    );
    echo json_encode(array('status' => 'ok', 'msg' => $printimage || ''));
}

function ResizePngImage($image, $max_width, $max_height)
{
	$old_width  = imagesx($image);
	$old_height = imagesy($image);
	$scale      = min($max_width/$old_width, $max_height/$old_height);
	$new_width  = ceil($scale*$old_width);
	$new_height = ceil($scale*$old_height);
	$new = imagecreatetruecolor($new_width, $new_height);
	imagealphablending( $new, false );
	imagesavealpha( $new, true );
	imagecopyresized($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);

	return $new;
}
