<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

$file = $_POST['file'];
$filePathTmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

if (is_readable($filePathTmp)) {
    if (!unlink($filePathTmp)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not delete tmp file';
        logErrorAndDie($errormsg);
    }
}

echo json_encode([
    'success' => true,
]);
