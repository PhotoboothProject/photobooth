<?php
require_once '../lib/config.php';

if (!isset($config['webserver']['ip'])) {
    $SERVER_IP = $_SERVER['HTTP_HOST'];
} else {
    $SERVER_IP = $config['webserver']['ip'];
}

$filename = $_GET['filename'];
$api_path = getrootpath('../api');
include '../vendor/phpqrcode/qrlib.php';
$url = 'http://' . $SERVER_IP . $api_path . '/download.php?image=';
QRcode::png($url . $filename, false, QR_ECLEVEL_H, 10);
