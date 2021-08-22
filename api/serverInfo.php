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

    case 'nav-githead':
        $get_head = shell_exec('git rev-parse --is-inside-work-tree 2>/dev/null && git log --format="%h %s" -n 20 || false');
        $file_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'HEAD';
        $head_file = realpath($file_path);

        if (is_file($head_file)) {
            echo 'Latest commits:' . "\r\n";
            echo dumpfile($head_file, null);
        } elseif ($get_head) {
            echo 'Latest commits:' . "\r\n";
            echo $get_head;
        } else {
            echo 'Can not get latest commits of this Photobooth installation.';
        }
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
