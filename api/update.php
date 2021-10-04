<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

$ghscript = __DIR__ . DIRECTORY_SEPARATOR . '../resources/sh/check-github.sh';
$commitscript = __DIR__ . DIRECTORY_SEPARATOR . '../resources/sh/commit.sh';
$upddev = __DIR__ . DIRECTORY_SEPARATOR . '../resources/sh/update-dev.sh';
$updstable = __DIR__ . DIRECTORY_SEPARATOR . '../resources/sh/update-stable.sh';
$checkdeps = __DIR__ . DIRECTORY_SEPARATOR . '../resources/sh/check-dependencies.sh';

$mode = $_POST['mode'];

switch ($mode) {
    case 'check-git':
        $cmd = sprintf($ghscript);
        break;
    case 'commit':
        $cmd = sprintf($commitscript);
        break;
    case 'update-dev':
        $cmd = sprintf($upddev);
        break;
    case 'update-stable':
        $cmd = sprintf($updstable);
        break;
    case 'check-deps':
        $cmd = sprintf($checkdeps);
        break;
    default:
        $cmd = 'echo "Error!"';
        break;
}

$success = exec('bash ' . $cmd, $output, $retval);

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
    if ($config['dev']['enabled'] && $config['dev']['advanced_log']) {
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
    if ($config['dev']['enabled'] && $config['dev']['advanced_log']) {
        logError($LogData);
    }
    echo $LogString;
}
