<?php

require_once '../lib/boot.php';

use Photobooth\Photobooth;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance();
$logger->debug(basename($_SERVER['PHP_SELF']));

try {
    $photobooth = new Photobooth();
    $data = [
        'updateAvailable' => $photobooth->checkUpdate(),
        'currentVersion' => $photobooth->getVersion(),
        'availableVersion' => $photobooth->getLatestRelease(),
    ];
    $logger->info('Info', $data);
} catch (Exception $e) {
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
}

echo json_encode($data);
exit();
