<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Service\LoggerService;

header('Content-Type: application/json');
$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

checkCsrfOrFail($_POST);

$simpleExec = $config['preview']['simpleExec'];

function isRunning(int $pid, Photobooth\Logger\NamedLogger $logger): bool
{
    try {
        $result = (string)shell_exec(sprintf('ps %d', $pid));
        $result = (array)preg_split("/\n/", $result);
        if (count($result) > 2) {
            return true;
        }
    } catch (\Exception $e) {
        $logger->error($e->getMessage());
        return false;
    }

    return false;
}

if ($_POST['play'] === 'start') {
    $cmd = sprintf($config['commands']['preview']);
    if ($simpleExec) {
        exec($cmd);
        $data = [
            'isRunning' => true,
            'pid' => intval(1),
        ];
    } else {
        $pid = (int)exec($cmd, $out);
        sleep(3);
        $data = [
            'isRunning' => isRunning($pid, $logger),
            'pid' => intval($pid - 1),
            'cmd' => $cmd,
        ];
    }
} else {
    $killcmd = sprintf($config['commands']['preview_kill']);
    $success = exec($killcmd, $output, $retval);
    if ($success) {
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

$logger->debug('data', $data);

echo json_encode($data);
exit();
