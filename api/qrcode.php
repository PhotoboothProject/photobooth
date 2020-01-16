<?php
require_once('../lib/config.php');

if (!isset($config['webserver_ip'])) {
    $SERVER_IP = $_SERVER['HTTP_HOST'];
} else {
    $SERVER_IP = $config['webserver_ip'];
}

$filename = $_GET['filename'];
include('../vendor/phpqrcode/qrlib.php');
$url = 'http://'.$SERVER_IP.'/api/download.php?image=';
QRcode::png($url.$filename, false, QR_ECLEVEL_H, 10);