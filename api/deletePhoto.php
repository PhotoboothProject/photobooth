<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

$file = $_POST['file'];
$filePath = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filePathThumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$filePathKeying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$filePathTmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

if (!unlink($filePath) || !unlink($filePathThumb)) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not delete file';
    logErrorAndDie($errormsg);
}

if (is_readable($filePathKeying)) {
    if (!unlink($filePathKeying)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not delete keying file';
        logErrorAndDie($errormsg);
    }
}

if (!$config['picture']['keep_original']) {
    if (is_readable($filePathTmp)) {
        if (!unlink($filePathTmp)) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not delete tmp file';
            logErrorAndDie($errormsg);
        }
    }
}

if ($config['database']['enabled']) {
    deleteImageFromDB($file);
}

echo json_encode([
    'success' => true,
]);
