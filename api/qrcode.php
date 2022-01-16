<?php
require_once '../lib/config.php';
require_once '../lib/helper.php';

$filename = $_GET['filename'];

if ($config['qr']['append_filename']) {
    $url = $config['qr']['url'] . $filename;
} else {
    $url = $config['qr']['url'];
}

include '../vendor/phpqrcode/lib/full/qrlib.php';
QRcode::png($url, false, QR_ECLEVEL_H, 10);
