<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

$mode = $_POST['mode'];

switch ($mode) {
    case 'pre-command':
        $cmd = sprintf($config['pre_photo']['cmd']);
        break;
    case 'post-command':
        $cmd = sprintf($config['post_photo']['cmd'], $_POST['filename']);
        break;
    case 'reboot':
        $cmd = 'sudo ' . sprintf($config['reboot']['cmd']);
        break;
    case 'shutdown':
        $cmd = 'sudo ' . sprintf($config['shutdown']['cmd']);
        break;
    default:
        $cmd = 'echo "Error for mode ' . $mode . ' - command not defined in configuration"';
        break;
}

$success = exec($cmd, $output, $retval);

if (isset($success)) {
    switch ($retval) {
        case 127:
            $output = 'Command not found';
            $success = false;
            break;
        case 0:
            $success = true;
            break;
        default:
            $success = 'unknown';
            break;
    }

    $LogData = [
        'success' => $success,
        'output' => $output,
        'retval' => $retval,
        'command' => $cmd,
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 1) {
        logError($LogData);
    }
    echo $LogString;
} else {
    $LogData = [
        'success' => 'false',
        'command' => $cmd,
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    echo $LogString;
}
