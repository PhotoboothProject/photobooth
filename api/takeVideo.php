<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

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
    die(
        json_encode([
            'isRunning' => isRunning($pid),
            'pid' => $pid - 1,
        ])
    );
} elseif ($_POST['play'] === 'false') {
    $killcmd = sprintf($config['preview']['killcmd']);
    exec($killcmd);
    die(
        json_encode([
            'isRunning' => isRunning($_POST['pid']),
            'pid' => $_POST['pid'],
        ])
    );
}
