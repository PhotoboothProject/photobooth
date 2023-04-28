<?php
header('Content-Type: application/json');

require_once '../lib/printdb.php';

$action = $_GET['action'];

// Validate action
$validActions = ['getPrintCount', 'unlockPrint'];
if (!in_array($action, $validActions)) {
    $LogData = [
        'error' => 'Invalid action.',
    ];
    http_response_code(400);
    die(json_encode($LogData));
}

try {
    // Perform action
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
    }
} catch (Exception $e) {
    $LogData = [
        'error' => 'Internal server error.',
    ];
    http_response_code(500);
    die(json_encode($LogData));
}

// Add action to log data
$LogData['action'] = $action;

// Output response
die(json_encode($LogData));
