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
    $candidate = $_GET['dir'];
    // Strip traversal and normalize
    $candidate = str_replace(['..', '\\'], '', $candidate);
    $candidate = ltrim($candidate, '/');

    // Allow only paths under private/, resources/, or data/
    if (str_starts_with($candidate, 'private/')
        || str_starts_with($candidate, 'resources/')
        || str_starts_with($candidate, 'data/')
        || $candidate === 'demoframes'
    ) {
        $directory = $candidate;
    }
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
        throw new \Exception('Unsupported file extension: ' . $file_extension);
}

header('Content-type: ' . $ctype);
readfile($filename);
