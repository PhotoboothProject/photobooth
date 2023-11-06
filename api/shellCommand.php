<?php

require_once '../lib/boot.php';

use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$mode = $_POST['mode'];

if (empty($mode)) {
    $data = [
        'success' => 'false',
        'mode' => 'No mode defined.',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    die();
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
        $data = [
            'success' => 'false',
            'mode' => 'Unknown mode ' . $mode,
        ];
        $logger->debug('message', $data);
        echo json_encode($data);
        die();
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

    $data = [
        'success' => $success,
        'output' => $output,
        'retval' => $retval,
        'command' => $cmd,
    ];
    $logger->debug('data', $data);
} else {
    $data = [
        'success' => 'false',
        'command' => $cmd,
    ];
}

$logger->debug('data', $data);
echo json_encode($data);
exit();
