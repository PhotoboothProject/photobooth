<?php
require_once '../lib/config.php';
require_once '../lib/helper.php';

$filename = $_GET['filename'];
$photoboothUrl = getPhotoboothUrl();

include '../vendor/phpqrcode/lib/full/qrlib.php';
$url = $photoboothUrl . '/api/download.php?image=' . $filename;
QRcode::png($url, false, QR_ECLEVEL_H, 10);
