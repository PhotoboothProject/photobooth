<?php
header('Content-Type: application/json');

ob_start();
require_once 'config.php';
$output = ob_end_clean();

$content = $_GET['content'];

switch ($content) {
    case 'nav-remotebuzzerlog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['remotebuzzer']['logfile']);
        break;

    case 'nav-synctodrivelog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['synctodrive']['logfile']);
        break;

    case 'nav-myconfig':
        print_r($config);
        break;

    case 'nav-serverprocesses':
        echo shell_exec('/bin/ps -ef');
        break;

    case 'nav-bootconfig':
        echo dumpfile('/boot/config.txt');
        break;

    default:
        echo 'UNKNOWN COMMAND';
        break;
}

function dumpfile($file) {
    if (!file_exists($file)) {
        return 'INFO: File (' . $file . ') does not exist';
    } elseif (!is_file($file)) {
        return 'INFO: Path (' . $file . ') is not a file';
    } else {
        return file_get_contents($file);
    }
}

return true;
