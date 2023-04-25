<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

if (file_exists(PRINT_COUNTER)) {
    $count = file_get_contents(PRINT_COUNTER);
} else {
    $count = 0;
}

if (file_exists(PRINT_LOCKFILE)) {
    $locked = true;
} else {
    $locked = false;
}

$LogData = [
    'count' => $count,
    'locked' => $locked,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
die($LogString);
