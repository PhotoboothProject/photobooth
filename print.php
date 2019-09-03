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

// exit with error
if(!file_exists($filename_source)) {
    echo json_encode(array('status' => sprintf('file "%s" not found', $filename_source)));
} else {
    // print
    // copy and merge
    if(!file_exists($filename_print)) {
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
        $print = imagecreatetruecolor($newwidth, $newheight);

        imagefill($print, 0, 0, imagecolorallocate($print, 255, 255, 255));
        imagecopy($print, $source , 0, 0, 0, 0, $width, $height);
        imagecopyresized($print, $code, $width, 0, 0, 0, ($height / 2), ($height / 2), imagesx($code), imagesy($code));

        imagejpeg($print, $filename_print);
        imagedestroy($print);
        imagedestroy($code);
        imagedestroy($source);
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
