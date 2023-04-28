<?php
header('Content-Type: application/json');

require_once '../lib/printdb.php';

$content = $_GET['action'];

switch ($content) {
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
            'error' => $content . ' unknown.',
        ];
        break;
}

$LogString = json_encode($LogData);
die($LogString);
