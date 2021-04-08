<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

$mode = $_POST['mode'];

switch ($mode) {
    case 'pre-command':
        $cmd = sprintf($config['pre_photo']['cmd']);
        break;
    case 'post-command':
        $cmd = sprintf($config['post_photo']['cmd']);
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

    echo json_encode([
        'success' => $success,
        'output' => $output,
        'retval' => $retval,
        'command' => $cmd,
    ]);
} else {
    echo json_encode([
        'success' => 'false',
        'command' => $cmd,
    ]);
}
