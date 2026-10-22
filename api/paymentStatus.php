<?php

declare(strict_types=1);

use Photobooth\Utility\PathUtility;

require_once dirname(__DIR__) . '/lib/boot.php';

header('Content-Type: application/json');

$jobFile = PathUtility::getAbsolutePath('private/photobooth_current_print.json');

if (!is_file($jobFile)) {
    echo json_encode([
        'status' => 'missing',
        'paid' => false,
        'printed' => false,
    ]);
    exit;
}

$data = json_decode((string)file_get_contents($jobFile), true);

if (!is_array($data)) {
    echo json_encode([
        'status' => 'invalid',
        'paid' => false,
        'printed' => false,
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'paid' => (bool)($data['paid'] ?? false),
    'printed' => (bool)($data['printed'] ?? false),
    'filename' => (string)($data['filename'] ?? ''),
]);
