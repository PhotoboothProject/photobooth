<?php
header('Content-Type: application/json');

ob_start();
require_once 'config.php';
$output = ob_end_clean();

$content = $_GET['content'];

switch ($content) {
    case 'nav-remotebuzzerlog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['remotebuzzer']['logfile'], true);
        break;

    case 'nav-synctodrivelog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['synctodrive']['logfile'], true);
        break;

    case 'nav-myconfig':
        print_r($config);
        break;

    case 'nav-serverprocesses':
        echo shell_exec('/bin/ps -ef');
        break;

    case 'nav-bootconfig':
        echo dumpfile('/boot/config.txt', null);
        break;

    case 'nav-cameralog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['take_picture']['logfile'], null);
        break;

    default:
        echo 'Unknown debug panel parameter';
        break;
}

function dumpfile($file, $devModeRequired) {
    global $config;

    if ($devModeRequired !== null && $config['dev']['enabled'] !== $devModeRequired) {
        return 'INFO: Dev mode is ' . ($config['dev']['enabled'] ? 'enabled - please disable' : 'disabled - please enable') . ' to see logs';
    }

    if (!file_exists($file)) {
        return 'INFO: File (' . $file . ') does not exist';
    } elseif (!is_file($file)) {
        return 'INFO: Path (' . $file . ') is not a file';
    } else {
        return file_get_contents($file);
    }
}

return true;
