<?php

require_once '../lib/boot.php';

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageService;

header('Content-Type: application/json');
checkCsrfOrFail($_POST);

$ftpData = $_POST['ftp'] ?? [];

$type = (string) ($ftpData['type'] ?? 'ftp');
$host = (string) ($ftpData['baseURL'] ?? '');
$port = (int) ($ftpData['port'] ?? 21);
$username = (string) ($ftpData['username'] ?? '');
$password = (string) ($ftpData['password'] ?? '');
$path = (string) ($_POST['path'] ?? '/');

if ($host === '' || $username === '') {
    echo json_encode(['error' => 'Missing connection parameters']);
    exit();
}

// If password is empty, use the saved (decrypted) config password
if ($password === '') {
    $savedConfig = ConfigurationService::getInstance()->getConfiguration();
    $password = (string) ($savedConfig['ftp']['password'] ?? '');
}

try {
    $folders = RemoteStorageService::listFolders([
        'type' => $type,
        'baseURL' => $host,
        'port' => $port,
        'username' => $username,
        'password' => $password,
    ], $path);

    echo json_encode(['folders' => $folders]);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit();
