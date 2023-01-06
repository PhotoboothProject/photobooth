<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

$images = [];
$unavailableImages = [];
$failedImages = [];
$file = $_POST['file'];
$success = true;
$images = [
    $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file,
    $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file,
    $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file,
];

if (!$config['picture']['keep_original']) {
    $images[] = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
}

foreach ($images as $image) {
    if (is_readable($image)) {
        if (!unlink($image)) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not delete ' . $image;
            logError($errormsg);
            $success = false;
            $failedImages[] = $image;
        }
    } else {
        $unavailableImages[] = $image;
    }
}

if ($config['database']['enabled']) {
    deleteImageFromDB($file);
}

$LogData = [
    'success' => $success,
    'file' => $file,
    'unavailable' => $unavailableImages,
    'failed' => $failedImages,
];
$LogString = json_encode($LogData);
if (!$success || $config['dev']['loglevel'] > 1) {
    logError($LogData);
}
echo $LogString;
