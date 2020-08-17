<?php
header('Content-Type: application/json');

require_once('../lib/db.php');
require_once('../lib/config.php');

if (empty($_POST['file'])) {
    die(json_encode([
        'error' => 'No file provided'
    ]));
}

$file = $_POST['file'];
$filePath = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filePathThumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

// Only jpg/jpeg are supported
$imginfo = getimagesize($filePath);
$mimetype = $imginfo['mime'];
if ($mimetype != 'image/jpg' && $mimetype != 'image/jpeg') {
    die(json_encode([
        'error' => 'The source file type ' . $mimetype . ' is not supported'
    ]));
}

if (!unlink($filePath) || !unlink($filePathThumb)) {
    die(json_encode([
        'error' => 'Could not delete file',
    ]));
}

deleteImageFromDB($file);

echo json_encode([
    'success' => true,
]);
