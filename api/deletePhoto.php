<?php

require_once '../lib/boot.php';

use Photobooth\FileDelete;
use Photobooth\DatabaseManager;
use Photobooth\Helper;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance();
$logger->debug(basename($_SERVER['PHP_SELF']));

try {
    if (empty($_POST['file'])) {
        throw new Exception('No file provided');
    }
} catch (Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
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

if ($config['ftp']['enabled'] && $config['ftp']['delete']) {
    $ftp = ftp_ssl_connect($config['ftp']['baseURL'], $config['ftp']['port']);

    // login to ftp server
    $login_result = ftp_login($ftp, $config['ftp']['username'], $config['ftp']['password']);

    if (!$login_result) {
        $message = 'Can\'t connect to FTP Server!';
        $logger->error($message, $config['ftp']);
        echo json_encode(['error' => $message]);
        die();
    }

    $remote_dest = empty($config['ftp']['baseFolder']) ? '' : DIRECTORY_SEPARATOR . $config['ftp']['baseFolder'] . DIRECTORY_SEPARATOR;

    $remote_dest .= $config['ftp']['folder'] . DIRECTORY_SEPARATOR . Helper::slugify($config['ftp']['title']);
    if ($config['ftp']['appendDate']) {
        $remote_dest .= DIRECTORY_SEPARATOR . date('Y/m/d');
    }

    @Helper::cdFTPTree($ftp, $remote_dest);

    $delete_result = ftp_delete($ftp, $file);

    if (!$delete_result) {
        $message = 'Unable to delete file on ftp server ' . $file;
        $logger->error($message, $config['ftp']);
        echo json_encode(['error' => $message]);
        die();
    }

    if ($config['ftp']['upload_thumb']) {
        $delete_result = ftp_delete($ftp, 'tmb_' . $file);

        if (!$delete_result) {
            $message = 'Unable to delete thumb on ftp server ' . $file;
            $logger->error($message, $config['ftp']);
            echo json_encode(['error' => $message]);
            die();
        }
    }

    // close the connection
    ftp_close($ftp);
}

if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    $Logger->addLogData($logData);
    $Logger->logToFile();
}

$logString = json_encode($logData);
echo $logString;
exit();
