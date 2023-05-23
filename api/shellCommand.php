<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

$mode = $_POST['mode'];

if (empty($mode)) {
    $LogData = [
        'success' => 'false',
        'mode' => 'No mode defined.',
    ];
    if ($config['dev']['loglevel'] > 0) {
        $Logger->addLogData($LogData);
        $Logger->logToFile();
    }

    $LogString = json_encode($LogData);
    die($LogString);
}

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
        $LogData = [
            'success' => 'false',
            'mode' => 'Unknown mode ' . $mode,
        ];
        if ($config['dev']['loglevel'] > 0) {
            $Logger->addLogData($LogData);
            $Logger->logToFile();
        }

        $LogString = json_encode($LogData);
        die($LogString);
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
    ];
    if ($config['dev']['loglevel'] > 1) {
        $Logger->addLogData($LogData);
    }
} else {
    $LogData = [
        'success' => 'false',
        'command' => $cmd,
    ];
    if ($config['dev']['loglevel'] > 0) {
        $Logger->addLogData($LogData);
    }
}
if ($config['dev']['loglevel'] > 0) {
    $Logger->logToFile();
}

$LogString = json_encode($LogData);
echo $LogString;
exit();
