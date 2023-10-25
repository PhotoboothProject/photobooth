<?php

require_once '../lib/boot.php';

use Photobooth\DataLogger;

header('Content-Type: application/json');

$logger = new DataLogger(PHOTOBOOTH_LOG);
$logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
$simpleExec = $config['preview']['simpleExec'];

function isRunning($pid, $logger)
{
    try {
        $result = shell_exec(sprintf('ps %d', $pid));

        if (count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
    } catch (Exception $e) {
        $ErrorData = ['exception' => $e->getMessage()];
        $logger->addLogData($ErrorData);
        return false;
    }

    return false;
}

if ($_POST['play'] === 'start') {
    $cmd = sprintf($config['preview']['cmd']);
    if ($simpleExec) {
        exec($cmd);
        $data = [
            'isRunning' => true,
            'pid' => intval(1),
        ];
    } else {
        $pid = exec($cmd, $out);
        sleep(3);
        $data = [
            'isRunning' => isRunning($pid, $logger),
            'pid' => $pid - 1,
            'cmd' => $cmd,
        ];
    }
} else {
    $killcmd = sprintf($config['preview']['killcmd']);
    $success = exec($killcmd, $output, $retval);
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
    }
    if ($simpleExec) {
        $data = [
            'isRunning' => false,
            'pid' => intval(0),
        ];
    } else {
        $data = [
            'isRunning' => isRunning($_POST['pid'], $logger),
            'cmd' => $killcmd,
            'pid' => intval($_POST['pid']),
        ];
    }
}
if ($config['dev']['loglevel'] > 1) {
    $logger->addLogData($data);
    $logger->logToFile();
}

echo json_encode($data);
exit();
