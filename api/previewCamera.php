<?php

header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
$simpleExec = $config['preview']['simpleExec'];

function isRunning($pid, $Logger)
{
    try {
        $result = shell_exec(sprintf('ps %d', $pid));

        if (count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
    } catch (Exception $e) {
        $ErrorData = ['exception' => $e->getMessage()];
        $Logger->addLogData($ErrorData);
        return false;
    }

    return false;
}

if ($_POST['play'] === 'start') {
    $cmd = sprintf($config['preview']['cmd']);
    if ($simpleExec) {
        exec($cmd);
        $LogData = [
            'isRunning' => true,
            'pid' => intval(1),
        ];
    } else {
        $pid = exec($cmd, $out);
        sleep(3);
        $LogData = [
            'isRunning' => isRunning($pid, $Logger),
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
        $LogData = [
            'isRunning' => false,
            'pid' => intval(0),
        ];
    } else {
        $LogData = [
            'isRunning' => isRunning($_POST['pid'], $Logger),
            'cmd' => $killcmd,
            'pid' => intval($_POST['pid']),
        ];
    }
}
if ($config['dev']['loglevel'] > 1) {
    $Logger->addLogData($LogData);
    $Logger->logToFile();
}
$LogString = json_encode($LogData);
echo $LogString;
exit();
