<?php
$filename = $_GET['filename'];
include('resources/lib/phpqrcode/qrlib.php');
$url = 'http://'.$_SERVER['HTTP_HOST'].'/download.php?image=';
QRcode::png($url.$filename, false, QR_ECLEVEL_H, 10);