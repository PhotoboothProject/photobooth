<?php
header('Content-Type: application/json');

require_once '../lib/printdb.php';

$action = $_GET['action'];

switch ($action) {
    case 'getPrintCount':
        $count = getPrintCountFromCounter();
        $locked = isPrintLocked();

        $LogData = [
            'count' => $count,
            'locked' => $locked,
        ];
        break;

    case 'unlockPrint':
        $unlock = unlockPrint();

        $LogData = [
            'success' => $unlock,
        ];
        break;

    default:
        $LogData = [
            'error' => 'Action unknown.',
        ];
        break;
}

$LogData[] = ['action' => $action];
$LogString = json_encode($LogData);
die($LogString);
