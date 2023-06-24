<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/photobooth.php';

function getLogData($debugLevel) {
    $Logger = new DataLogger(PHOTOBOOTH_LOG);
    $Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

    try {
        $photobooth = new Photobooth();
        $logData = [
            'updateAvailable' => $photobooth->checkUpdate(),
            'currentVersion' => $photobooth->getPhotoboothVersion(),
            'availableVersion' => $photobooth->getLatestRelease(),
        ];

        if ($debugLevel > 0) {
            $Logger->addLogData($logData);
            $Logger->logToFile();
        }
    } catch (Exception $e) {
        $logData = ['error' => $e->getMessage()];
        $Logger->addLogData($logData);
        $Logger->logToFile();
    }

    return $logData;
}

$logData = getLogData($config['dev']['loglevel']);
$logString = json_encode($logData);
echo $logString;
exit();
