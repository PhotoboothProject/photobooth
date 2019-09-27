<?php
$filename = $_GET['filename'];
include('../vendor/phpqrcode/qrlib.php');
$url = 'http://'.$_SERVER['HTTP_HOST'].'/api/download.php?image=';
QRcode::png($url.$filename, false, QR_ECLEVEL_H, 10);