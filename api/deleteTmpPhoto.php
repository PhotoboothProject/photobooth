<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

if (empty($_POST['file'])) {
    die(
        json_encode([
            'error' => 'No file provided',
        ])
    );
}

$file = $_POST['file'];
$filePathTmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

if (is_readable($filePathTmp)) {
    if (!unlink($filePathTmp)) {
        die(
            json_encode([
                'error' => 'Could not delete tmp file',
            ])
        );
    }
}

echo json_encode([
    'success' => true,
]);
