<?php

use Photobooth\Utility\PathUtility;
use Photobooth\Utility\QrCodeUtility;

require_once '../lib/boot.php';

$filename = (isset($_GET['filename']) && $_GET['filename']) != '' ? $_GET['filename'] : false;

if ($filename || !$config['qr']['append_filename']) {
    if ($config['ftp']['enabled'] && $config['ftp']['useForQr']) {
        $url = $config['ftp']['processedTemplate'] . DIRECTORY_SEPARATOR . $filename;
    } elseif ($config['qr']['append_filename']) {
        $url = PathUtility::getPublicPath($config['qr']['url'] . $filename, true);
    } else {
        $url = PathUtility::getPublicPath($config['qr']['url'], true);
    }
    try {
        $result = QrCodeUtility::create($url);
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
    } catch (\Exception $e) {
        http_response_code(500);
        echo 'Error generating QR Code.';
        if ($config['dev']['loglevel'] > 1) {
            echo $e->getMessage();
        }
    }
} else {
    http_response_code(400);
    echo 'No filename defined.';
}
exit();
