<?php

require_once '../lib/boot.php';

use Photobooth\Service\RemoteStorageService;

header('Content-Type: application/json');

$remoteStorage = RemoteStorageService::getInstance();
if (!$remoteStorage->testConnection()) {
    echo json_encode([
        'response' => 'error',
        'message' => 'ftp:no_connection',
        'missing' => [],
    ]);
    exit();
}

echo json_encode([
    'response' => 'success',
    'message' => 'ftp:connected',
    'missing' => [],
]);
exit();
