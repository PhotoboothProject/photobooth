<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\FileDelete;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$remoteStorage = RemoteStorageService::getInstance();

try {
    if (empty($_POST['file'])) {
        throw new \Exception('No file provided');
    }
} catch (\Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

$file = $_POST['file'];
$paths = [
    FolderEnum::IMAGES->absolute(),
    FolderEnum::THUMBS->absolute(),
    FolderEnum::KEYING->absolute(),
];

if (!$config['picture']['keep_original']) {
    $paths[] = FolderEnum::TEMP->absolute();
}

$delete = new FileDelete($file, $paths);
$delete->deleteFiles();
$logData = $delete->getLogData();

if ($config['database']['enabled']) {
    $database = DatabaseManagerService::getInstance();
    $database->deleteContentFromDB($file);
}

if ($config['ftp']['enabled'] && $config['ftp']['delete']) {
    $remoteStorage->delete($remoteStorage->getStorageFolder() . '/images/' . $file);
    $remoteStorage->delete($remoteStorage->getStorageFolder() . '/thumbs/' . $file);
}

if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    $logger->debug('data', $logData);
}

$logString = json_encode($logData);
echo $logString;
exit();
