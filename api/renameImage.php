<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Helper;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$logString = json_encode($_POST);
try {
    if (empty($_POST['image'])) {
        throw new Exception('No file provided');
    }
} catch (Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
}
$logData = [];
$file = $_POST['image'];
$fullName = filter_var($_POST['fullName'], FILTER_UNSAFE_RAW);
$newFileName = Helper::slugify($fullName) . '_' . $file;
$logData['success'] = false;

if(file_exists($config['foldersAbs']['namedImages'] . DIRECTORY_SEPARATOR . $newFileName)) {
    $logData['fileExists'] = 'Same image already exists - popup will close in 3 seconds';
} else {
    if (!copy(
        $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file,
        $config['foldersAbs']['namedImages'] . DIRECTORY_SEPARATOR . $newFileName
    )) {
        $logData['failedCopy'] = json_encode('failed to copy');
    } else {
        $logData['success'] = true;
    }
}

// TODO: FTP COPY NAMED IMAGE
/*if ($config['ftp']['enabled'] && $config['ftp']['delete']) {
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
}*/

// TODO: LOG DATA
/*if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    $logger->debug('data', $logData);
}*/

$logString = json_encode($logData);
echo $logString;
exit();
