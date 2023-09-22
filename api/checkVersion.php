<?php

require_once '../lib/boot.php';

use Photobooth\Photobooth;
use Photobooth\DataLogger;

header('Content-Type: application/json');

function getLogData($debugLevel)
{
    $Logger = new DataLogger(PHOTOBOOTH_LOG);
    $Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

    try {
        $photobooth = new Photobooth();
        $logData = [
            'updateAvailable' => $photobooth->checkUpdate(),
            'currentVersion' => $photobooth->getVersion(),
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
