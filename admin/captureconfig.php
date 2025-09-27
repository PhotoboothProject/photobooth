<?php

/** @var array $config */
require_once '../lib/boot.php';

use Photobooth\Environment;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\PathUtility;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

header('Content-Type: application/json');

$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$configurationService = ConfigurationService::getInstance();
$logger->debug('Saving Photobooth configuration for go2rtc...');

$config['commands']['preview'] = '';
$config['commands']['take_picture'] = 'capture %s';

$config['picture']['cheese_time'] = '0';

$config['preview']['mode'] = 'url';
$config['preview']['url'] = 'url("http://' . Environment::getIp() . ':1984/api/stream.mjpeg?src=photobooth")';
$config['preview']['camTakesPic'] = false;

try {
    $configurationService->update($config);
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

// Kill service daemons after config has changed
ProcessService::getInstance()->shutdown();

// return to Adminpanel
header('location: ' . PathUtility::getPublicPath('admin'));
exit();
