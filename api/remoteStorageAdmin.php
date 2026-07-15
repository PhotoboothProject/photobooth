<?php

/** @var array $config */

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageQueueService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('remotestorage');
$queue = RemoteStorageQueueService::getInstance();

// Inspect the upload queue: per-file status plus counts.
if (isset($_GET['list'])) {
    echo json_encode([
        'counts' => $queue->getCounts(),
        'entries' => $queue->getEntries(),
    ]);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

checkCsrfOrFail($_POST);

$action = $_POST['action'] ?? null;

if ($action === 'retry') {
    $file = null;
    if (!empty($_POST['file'])) {
        $file = basename((string) $_POST['file']);
        if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file name provided']);
            exit();
        }
    }
    $count = $queue->retryFailed($file);
    $logger->info('Failed uploads reset to pending', ['count' => $count, 'file' => $file]);
    echo json_encode([
        'status' => 'success',
        'retried' => $count,
        'hint' => 'trigger api/remoteStorageUpload.php to start uploading',
    ]);
    exit();
}

if ($action === 'clear') {
    $count = $queue->clearFinished();
    $logger->info('Finished upload entries cleared', ['count' => $count]);
    echo json_encode(['status' => 'success', 'cleared' => $count]);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action.']);
exit();
