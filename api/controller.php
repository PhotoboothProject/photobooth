<?php
session_start();
require_once '../lib/config.php';

// includes
include_once 'keypad.php';

// KEYPAD LOGIN
if (isset($_POST['controller']) and $_POST['controller'] == 'keypadLogin') {
    $userPin = $_POST['pin'];

    $keypad = new Keypad();
    $return = $keypad->keypadLogin($userPin, $config['login']['pin']);

    echo json_encode($return);
}
?>
