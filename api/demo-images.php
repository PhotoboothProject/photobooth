<?php

require_once '../lib/boot.php';

use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

// Important: Set Content-Type to JSON
header('Content-Type: application/json');

// Get the desired number of images from the GET parameter 'count'
// Default to 1 if 'count' is not set.
$count = isset($_GET['count']) ? (int)$_GET['count'] : 1;

// Ensure count is at least 1
if ($count < 1) {
    $count = 1;
}

// Get demo images using your existing ImageUtility
$demoImages = ImageUtility::getDemoImages($count);

// Convert internal paths to public URLs
$publicImagePaths = array_map(fn ($img) => PathUtility::getPublicPath($img), $demoImages);

// Return image paths as JSON
echo json_encode($publicImagePaths);

// Terminate script execution to ensure nothing extra is outputted
exit;