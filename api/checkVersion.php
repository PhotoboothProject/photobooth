<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/helper.php';

function getLogData($config) {
    try {
        $photobooth = new Photobooth();

        $logData = [
            'update_available' => $photobooth->checkUpdate(),
            'current_version' => $photobooth->getPhotoboothVersion(),
            'available_version' => $photobooth->getLatestRelease(),
            'php_script' => basename($_SERVER['PHP_SELF']),
        ];

        if ($config['dev']['loglevel'] > 0) {
            logError($logData);
        }
    } catch (Exception $e) {
        logError($e->getMessage());
        $logData = ['error' => $e->getMessage()];
    }

    return $logData;
}

$logData = getLogData($config);
$logString = json_encode($logData);
die($logString);
