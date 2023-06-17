<?php

header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/deleteFile.php';

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

if ($config['ftp']['enabled'] && $config['ftp']['delete']) {
    $ftp = ftp_ssl_connect($config['ftp']['baseURL'], $config['ftp']['port']);

    // login to ftp server
    $login_result = ftp_login($ftp, $config['ftp']['username'], $config['ftp']['password']);

    if (!$login_result) {
        $Logger->logErrorAndDie("Can't connect to FTP Server!");
    }

    $remote_dest = empty($config['ftp']['baseFolder']) ? '' : DIRECTORY_SEPARATOR . $config['ftp']['baseFolder'] . DIRECTORY_SEPARATOR;

    $remote_dest .= $config['ftp']['folder'] . DIRECTORY_SEPARATOR . Helper::slugify($config['ftp']['title']);
    if ($config['ftp']['appendDate']) {
        $remote_dest .= DIRECTORY_SEPARATOR . date('Y/m/d');
    }

    @Helper::cdFTPTree($ftp, $remote_dest);

    $delete_result = ftp_delete($ftp, $file);

    if (!$delete_result) {
        $ErrorData = [
            'error' => 'Unable to delete file on ftp server ' . $file,
        ];

        $Logger->logToFile($ErrorData);
    }

    if ($config['ftp']['upload_thumb']) {
        $delete_result = ftp_delete($ftp, 'tmb_' . $file);

        if (!$delete_result) {
            $ErrorData = [
                'error' => 'Unable to delete thumb on ftp server ' . $file,
            ];

            $Logger->logToFile($ErrorData);
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
