<?php

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

try {
    $applicationService = ApplicationService::getInstance();
    $data = [
        'updateAvailable' => $applicationService->checkUpdate(),
        'currentVersion' => $applicationService->getVersion(),
        'availableVersion' => $applicationService->getLatestRelease(),
    ];
    $logger->info('Info', $data);
} catch (\Exception $e) {
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
}

echo json_encode($data);
exit();
