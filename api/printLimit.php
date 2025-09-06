<?php

/** @var array $config */
require_once '../lib/boot.php';

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;

header('Content-Type: application/json');

$loggerService = LoggerService::getInstance();
$configurationService = ConfigurationService::getInstance();
$printManager = PrintManagerService::getInstance();

$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$increaseCount = $_GET['increaseCount'] ?? null;

if ($increaseCount === null || !filter_var($increaseCount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
    $LogData = [
        'error' => 'Invalid count. It must be a non-negative integer.',
    ];
    http_response_code(400);
    die(json_encode($LogData));
}

$increaseCount = (int) $increaseCount;

$logger->debug('Saving Photobooth configuration with new print limit');
$config['print']['limit'] = ($config['print']['limit'] ?? 0) + $increaseCount;

try {
    $configurationService->update($config);
    $printManager->unlockPrint();
    $logger->debug('New config saved.');
    echo json_encode([
        'status' => 'success',

        'message' => 'New config saved.',
    ]);
} catch (\Exception $exception) {
    $logger->error('ERROR: Config can not be saved!');
    echo json_encode([
        'status' => 'error',
            'message' => $exception->getMessage(),
    ]);
}

exit();
