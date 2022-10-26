<?php
require_once __DIR__ . '/config.php';

function logError($data) {
    global $config;
    $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['dev']['logfile'];

    $file_data = date('c') . ":\n" . print_r($data, true) . "\n";
    if (is_file($logfile)) {
        $file_data .= file_get_contents($logfile);
    }
    file_put_contents($logfile, $file_data);
}

function logErrorAndDie($errormsg) {
    $ErrorData = [
        'error' => $errormsg,
    ];
    $ErrorString = json_encode($ErrorData);
    logError($ErrorData);
    die($ErrorString);
}
