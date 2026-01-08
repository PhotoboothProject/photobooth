<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\FileDelete;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\ImageMetadataCacheService;
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
    $file = basename((string)$_POST['file']);
    if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
        throw new \Exception('Invalid file name provided');
    }
} catch (\Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

$fileBaseName = pathinfo($file, PATHINFO_FILENAME);
$filesToDelete = [$file];
$paths = [
    FolderEnum::IMAGES->absolute(),
    FolderEnum::THUMBS->absolute(),
    FolderEnum::KEYING->absolute(),
];

$paths[] = FolderEnum::TEMP->absolute();

// Collect possible single images belonging to the collage
// Catch any single images that match the base pattern, even if keep_single_images is off or limit changed
foreach ($paths as $path) {
    $matches = glob($path . DIRECTORY_SEPARATOR . $fileBaseName . '-*.jpg');
    if ($matches !== false) {
        foreach ($matches as $matchedFile) {
            $filesToDelete[] = basename($matchedFile);
        }
    }
}

$filesToDelete = array_values(array_unique($filesToDelete));

$logData = [
    'success' => true,
    'file' => $file,
    'files' => [],
];

// Remove cached metadata for this file and its thumb, if present
ImageMetadataCacheService::getInstance()->remove(FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $file);
ImageMetadataCacheService::getInstance()->remove(FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $file);

foreach ($filesToDelete as $fileName) {
    $delete = new FileDelete($fileName, $paths, (bool) $config['picture']['keep_original']);
    $delete->deleteFiles();
    $singleLogData = $delete->getLogData();
    $logData['files'][$fileName] = $singleLogData;
    if (!$singleLogData['success']) {
        $logData['success'] = false;
    }

    if ($config['database']['enabled']) {
        $database = DatabaseManagerService::getInstance();
        $database->deleteContentFromDB($fileName);
    }

    if ($config['ftp']['enabled'] && $config['ftp']['delete']) {
        $remoteStorage->delete($remoteStorage->getStorageFolder() . '/images/' . $fileName);
        $remoteStorage->delete($remoteStorage->getStorageFolder() . '/thumbs/' . $fileName);
    }
}

if (!$logData['success'] || $config['dev']['loglevel'] > 1) {
    $logger->debug('data', $logData);
}

$logString = json_encode($logData);
echo $logString;
exit();
