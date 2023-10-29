<?php

require_once '../lib/boot.php';

use Photobooth\Utility\AdminKeypad;

header('Content-Type: application/json');

// KEYPAD LOGIN
if (isset($_POST['controller']) and $_POST['controller'] == 'keypadLogin') {
    $data = [
        'state' => AdminKeypad::login($_POST['pin'] ?? '', $config['login'])
    ];
    echo json_encode($data);
    exit();
}
