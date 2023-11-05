<?php

require_once '../lib/boot.php';

use Photobooth\Service\DatabaseManagerService;

$database = DatabaseManagerService::getInstance();

// Check if there is a request for the status of the database
if (isset($_GET['status'])) {
    // Request for DB-Status,
    // Currently reports back the DB-Size to give the Client the ability
    // to detect changes
    $resp = ['dbsize' => $database->getDBSize()];
    exit(json_encode($resp));
} else {
    http_response_code(400);
    echo 'Invalid request.';
    exit();
}
