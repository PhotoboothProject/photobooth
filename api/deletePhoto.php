<?php

header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/deleteFile.php';
require_once '../lib/nextcloud.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

try {
    if (empty($_POST['file'])) {
        throw new Exception('No file provided');
    }
} catch (Exception $e) {
    // Handle the exception
    $ErrorData = [
        'error' => $e->getMessage(),
    ];

    $Logger->logToFile($ErrorData);

    $ErrorString = json_encode($ErrorData);
    die($ErrorString);
}

$file = $_POST['file'];
$paths = [$config['foldersAbs']['images'], $config['foldersAbs']['thumbs'], $config['foldersAbs']['keying']];

if (!$config['picture']['keep_original']) {
    $paths[] = $config['foldersAbs']['tmp'];
}

$delete = new FileDelete($file, $paths);
$delete->deleteFiles();
$logData = $delete->getLogData();

if ($config['database']['enabled']) {
    $database = new DatabaseManager();
    $database->db_file = DB_FILE;
    $database->file_dir = IMG_DIR;
    $database->deleteContentFromDB($file);
}

// Check for Nextcloud Enabled and Upload image to Nextcloud
if ($config['nextcloud']['enabled'] && !$config['nextcloud']['mntEnabled']) {
    $nextcloud = new Nextcloud($config['nextcloud'], $Logger);
    $nextcloud->deleteImage($file);
}

if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    $Logger->addLogData($logData);
    $Logger->logToFile();
}

$logString = json_encode($logData);
echo $logString;
exit();
