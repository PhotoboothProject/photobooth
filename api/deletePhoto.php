<?php

header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/deleteFile.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
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

$logString = json_encode($logData);
if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    logError($logData);
}

echo $logString;
