<?php
require_once '../lib/config.php';
require_once '../lib/nextcloud.php';

if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    throw new Exception('Filename not defined.');
}

$filename = $_GET['filename'];
$url = null;

// Check for the various conditions and execute the corresponding actions

if ($config['nextcloud']['enabled'] && $config['nextcloud']['fileshare']) {
    $shareLink = new NextcloudShareLink();
    $shareLink->nextcloudMnt = $config['nextcloud']['mnt'];
    $shareLink->nextcloudUser = $config['nextcloud']['user'];
    $shareLink->nextcloudPass = $config['nextcloud']['pass'];
    $shareLink->nextcloudUrl = $config['nextcloud']['url'];
    $shareLink->nextcloudPath = $config['nextcloud']['path'];
    $url = $shareLink->generateShareLink($filename);
}

if ($url == null) {
    if ($config['qr']['append_filename']) {
        $url = $config['qr']['url'] . $filename;
    } else {
        $url = $config['qr']['url'];
    }
}

echo $url;
