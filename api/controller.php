<?php

require_once '../lib/boot.php';

// includes
include_once 'keypad.php';
include_once 'qr.php';

// KEYPAD LOGIN
if (isset($_POST['controller']) and $_POST['controller'] == 'keypadLogin') {
    $userPin = $_POST['pin'];

    $keypad = new Keypad();
    $return = $keypad->keypadLogin($userPin, $config['login']);

    echo json_encode($return);
}

if (isset($_POST['controller']) and $_POST['controller'] == 'getWifiQrCode') {
    $qrcode = new QrCodeClass();
    return $qrcode->getWifiQrCode($config['qr']);
}
if (isset($_POST['controller']) and $_POST['controller'] == 'getImageQrCode') {
    $qrcode = new QrCodeClass();
    $image = $_POST['image'];
    return $qrcode->getImageQrCode($config['qr'], $image);
}
