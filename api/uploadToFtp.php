<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
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

    $fileName = $_POST['file'];

    if (!$config['ftp']['enabled']) {
        echo json_encode(['success' => true, 'message' => 'FTP disabled']);
        exit();
    }

    $resultFile = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $fileName;
    $thumbFile = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $fileName;

    if (!file_exists($resultFile)) {
        // Check if it is a demo image
        if ($config['dev']['demo_images']) {
            $demoImage = FolderEnum::RESOURCES->absolute() . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'demo.jpg';
            if (file_exists($demoImage)) {
                $resultFile = $demoImage;
                // For demo images, we might not have a thumb, or use the same
                if (!file_exists($thumbFile)) {
                    $thumbFile = $demoImage;
                }
            } else {
                throw new \Exception('Demo image not found: ' . $demoImage);
            }
        } else {
            throw new \Exception('Image does not exist: ' . $resultFile);
        }
    }

    // Store images on remote storage
    $uploadSuccess = $remoteStorage->write($remoteStorage->getStorageFolder() . '/images/' . $fileName, (string) file_get_contents($resultFile));

    if (!$uploadSuccess) {
        throw new \Exception('Failed to upload image: ' . $fileName);
    }

    if (file_exists($thumbFile)) {
        $thumbSuccess = $remoteStorage->write($remoteStorage->getStorageFolder() . '/thumbs/' . $fileName, (string) file_get_contents($thumbFile));
        if (!$thumbSuccess) {
            $logger->error('Failed to upload thumbnail: ' . $fileName);
        }
    }

    if ($config['ftp']['create_webpage'] && !preg_match('/-\d+\.jpg$/', $fileName)) {
        $remoteStorage->createWebpage();
    }

    echo json_encode(['success' => true]);

} catch (\Throwable $e) {
    $logger->error($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
