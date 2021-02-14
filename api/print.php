<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/resize.php';
require_once '../lib/applyFrame.php';

if (empty($_GET['filename'])) {
    die(
        json_encode([
            'error' => 'No file provided',
        ])
    );
}

$filename = $_GET['filename'];
$filename_source = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $filename;
$filename_print = $config['foldersAbs']['print'] . DIRECTORY_SEPARATOR . $filename;
$filename_codes = $config['foldersAbs']['qrcodes'] . DIRECTORY_SEPARATOR . $filename;
$status = false;

// exit with error if file does not exist
if (!file_exists($filename_source)) {
    die(
        json_encode([
            'error' => "File $filename not found",
        ])
    );
}

// Only jpg/jpeg are supported
$imginfo = getimagesize($filename_source);
$mimetype = $imginfo['mime'];
if ($mimetype != 'image/jpg' && $mimetype != 'image/jpeg') {
    die(
        json_encode([
            'error' => 'The source file type ' . $mimetype . ' is not supported',
        ])
    );
}

// QR
if (!isset($config['webserver']['ip'])) {
    $SERVER_IP = $_SERVER['HTTP_HOST'];
} else {
    $SERVER_IP = $config['webserver']['ip'];
}

// text on print variables
$fontpath = __DIR__ . DIRECTORY_SEPARATOR . $config['textonprint']['font'];
$fontsize = $config['textonprint']['font_size'];
$fontlocx = $config['textonprint']['locationx'];
$fontlocy = $config['textonprint']['locationy'];
$linespacing = $config['textonprint']['linespace'];
$fontrot = $config['textonprint']['rotation'];
$line1text = $config['textonprint']['line1'];
$line2text = $config['textonprint']['line2'];
$line3text = $config['textonprint']['line3'];

// print frame
$print_frame = __DIR__ . DIRECTORY_SEPARATOR . $config['print']['frame'];

if (!file_exists($filename_print)) {
    // rotate image if needed
    list($width, $height) = getimagesize($filename_source);
    if ($width > $height) {
        $image = imagecreatefromjpeg($filename_source);
        imagejpeg($image, $filename_print);
        imagedestroy($image); // Destroy the created collage in memory
    } else {
        $image = imagecreatefromjpeg($filename_source);
        $resultRotated = imagerotate($image, 90, 0); // Rotate image
        imagejpeg($resultRotated, $filename_print);
        imagedestroy($image); // Destroy the created collage in memory
        // re-define width & height after rotation
        list($width, $height) = getimagesize($filename_print);
    }

    if ($config['print']['qrcode']) {
        // create qr code
        if (!file_exists($filename_codes)) {
            include '../vendor/phpqrcode/qrlib.php';
            $url = 'http://' . $SERVER_IP . '/api/download.php?image=';
            QRcode::png($url . $filename, $filename_codes, QR_ECLEVEL_H, 10);
        }

        // merge source and code
        $newwidth = $width + $height / 2;
        $newheight = $height;

        if ($config['print']['print_frame'] && !$config['picture']['take_frame']) {
            ApplyFrame($filename_print, $filename_print, $print_frame);
        }

        $source = imagecreatefromjpeg($filename_print);
        $code = imagecreatefrompng($filename_codes);
        $print = imagecreatetruecolor($newwidth, $newheight);

        imagefill($print, 0, 0, imagecolorallocate($print, 255, 255, 255));
        imagecopy($print, $source, 0, 0, 0, 0, $width, $height);
        imagecopyresized($print, $code, $width, 0, 0, 0, $height / 2, $height / 2, imagesx($code), imagesy($code));

        if ($config['textonprint']['enabled'] == true) {
            $fontcolour = imagecolorallocate($print, 0, 0, 0); // colour of font
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, $fontpath, $line1text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, $fontpath, $line2text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing * 2, $fontcolour, $fontpath, $line3text);
        }

        imagejpeg($print, $filename_print);
        imagedestroy($code);
        imagedestroy($source);
        imagedestroy($print);
    } else {
        if ($config['print']['print_frame'] && !$config['picture']['take_frame']) {
            ApplyFrame($filename_print, $filename_print, $print_frame);
        }

        if ($config['textonprint']['enabled'] == true) {
            $print = imagecreatefromjpeg($filename_print);
            $fontcolour = imagecolorallocate($print, 0, 0, 0); // colour of font
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, $fontpath, $line1text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, $fontpath, $line2text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing * 2, $fontcolour, $fontpath, $line3text);
            imagejpeg($print, $filename_print);
            imagedestroy($print);
        }
    }

    if ($config['print']['crop']) {
        $crop_width = $config['print']['crop_width'];
        $crop_height = $config['print']['crop_height'];
        ResizeCropImage($crop_width, $crop_height, $filename_print, $filename_print);
    }
}

// print image
$printimage = shell_exec(sprintf($config['print']['cmd'], $filename_print));

die(
    json_encode([
        'status' => 'ok',
        'msg' => $printimage || '',
    ])
);
