<?php
require_once '../lib/config.php';
require_once '../lib/helper.php';

$url = (isset($_GET['share_link']) && $_GET['share_link']) != '' ? $_GET['share_link'] : false;

if ($url || !$config['qr']['append_filename']) {
    try {
        include '../vendor/phpqrcode/lib/full/qrlib.php';
        switch ($config['qr']['ecLevel']) {
            case 'QR_ECLEVEL_L':
                $ecLevel = QR_ECLEVEL_L;
                break;
            case 'QR_ECLEVEL_M':
                $ecLevel = QR_ECLEVEL_M;
                break;
            case 'QR_ECLEVEL_Q':
                $ecLevel = QR_ECLEVEL_Q;
                break;
            case 'QR_ECLEVEL_H':
                $ecLevel = QR_ECLEVEL_H;
                break;
            default:
                $ecLevel = QR_ECLEVEL_M;
                break;
        }

        QRcode::png($url, false, $ecLevel, 8);
    } catch (Exception $e) {
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
