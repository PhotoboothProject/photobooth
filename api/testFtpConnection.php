<?php

/** @var array $config */

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Enum\RemoteStorageTypeEnum;
use Photobooth\Service\RemoteStorageService;

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

checkCsrfOrFail($_POST);

// Test the values currently entered in the admin form (they may not be
// saved yet), falling back to the saved configuration.
$settings = $config['ftp'];
$form = is_array($_POST['ftp'] ?? null) ? $_POST['ftp'] : [];
foreach (['host', 'username', 'password', 'title', 'path', 'url', 'qr_link'] as $key) {
    if (isset($form[$key]) && is_string($form[$key])) {
        $settings[$key] = $form[$key];
    }
}
if (isset($form['port']) && is_numeric($form['port'])) {
    $settings['port'] = (int) $form['port'];
}
if (isset($form['type']) && is_string($form['type'])) {
    $settings['type'] = RemoteStorageTypeEnum::tryFrom($form['type']) ?? $settings['type'];
}

$missing = [];
foreach (['host', 'username'] as $key) {
    if (trim((string) $settings[$key]) === '') {
        $missing[] = $key;
    }
}
if (!empty($missing)) {
    echo json_encode([
        'response' => 'error',
        'message' => 'ftp:missing_parameters',
        'missing' => $missing,
    ]);
    exit();
}

$remoteStorage = new RemoteStorageService($settings);
if (!$remoteStorage->testConnection()) {
    echo json_encode([
        'response' => 'error',
        'message' => 'ftp:no_connection',
        'missing' => [],
    ]);
    exit();
}

// Show what the path / URL settings resolve to, so the result of the
// placeholders can be checked before the first upload.
echo json_encode([
    'response' => 'success',
    'message' => 'ftp:connected',
    'missing' => [],
    'path' => '/' . $remoteStorage->getStorageFolder(),
    'link' => trim((string) $settings['url']) !== '' ? $remoteStorage->getPhotoUrl('example.jpg') : '',
]);
exit();
