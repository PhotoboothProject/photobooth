<?php

require_once '../lib/boot.php';

use Photobooth\Service\PrintManagerService;

header('Content-Type: application/json');

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

$printManager = PrintManagerService::getInstance();

try {
    // Perform action
    switch ($action) {
        case 'getPrintCount':
            $count = $printManager->getPrintCountFromCounter();
            $locked = $printManager->isPrintLocked();

            $LogData = [
                'count' => $count,
                'locked' => $locked,
            ];
            break;

        case 'unlockPrint':
            $unlock = $printManager->unlockPrint();

            $LogData = [
                'success' => $unlock,
            ];
            break;
    }
} catch (\Exception $e) {
    $LogData = [
        'error' => 'Internal server error.',
    ];
    http_response_code(500);
    die(json_encode($LogData));
}

// Add action to log data
$LogData['action'] = $action;

// Output response
echo json_encode($LogData);
exit();
