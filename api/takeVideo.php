<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

function isRunning($pid) {
    try {
        $result = shell_exec(sprintf('ps %d', $pid));

        if (count(preg_split("/\n/", $result)) > 2) {
            return true;
        }
    } catch (Exception $e) {
    }

    return false;
}

if ($_POST['play'] === 'true') {
    $cmd = sprintf($config['preview']['cmd']);
    $pid = exec($cmd, $out);
    sleep(3);
    $LogData = [
        'isRunning' => isRunning($pid),
        'pid' => $pid - 1,
        'php' => basename($_SERVER['PHP_SELF']),
    ];
} elseif ($_POST['play'] === 'false') {
    $killcmd = sprintf($config['preview']['killcmd']);
    if ($killcmd != '') {
        exec($killcmd);
    }

    $LogData = [
        'isRunning' => isRunning($_POST['pid']),
        'pid' => intval($_POST['pid']),
        'php' => basename($_SERVER['PHP_SELF']),
    ];
}
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
die($LogString);
