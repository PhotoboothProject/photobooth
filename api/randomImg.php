<?php

use Photobooth\Utility\ImageUtility;

require_once '../lib/boot.php';

// RANDOM FRAME/BACKGROUND/...
//
// This "script" allows to randomize images,
// backgrounds, canvas, frames, etc. so
// pictures taken are "funnier" and an element
// of "surprise".

$directory = 'demoframes';
if (isset($_GET['dir']) && !empty($_GET['dir'])) {
    $directory = $_GET['dir'];
}

$filename = ImageUtility::getRandomImageFromPath($directory);
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

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
