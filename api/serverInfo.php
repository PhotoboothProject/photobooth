<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

$content = $_GET['content'];

switch ($content) {
    case 'nav-remotebuzzerlog':
        echo file_get_contents($config['foldersAbs']['tmp'] . '/' . $config['remotebuzzer']['logfile']);
        break;

    case 'nav-synctodrivelog':
        echo file_get_contents($config['foldersAbs']['tmp'] . '/' . $config['synctodrive']['logfile']);
        break;

    case 'nav-myconfig':
        echo dumpfile($config['foldersAbs']['config'] . '/' . 'my.config.inc.php');
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
