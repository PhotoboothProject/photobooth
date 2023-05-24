<?php
require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/nextcloud.php';

if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    throw new Exception('Filename not defined.');
}

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

$nextcloud = new Nextcloud($config['nextcloud'], $Logger);
$shareData = $nextcloud->generateShareLink($_GET['filename'], $config['qr']);

if (isset($shareData['error']) || $config['dev']['loglevel'] > 1) {
    $Logger->logToFile();
}

echo $shareData['success'];
