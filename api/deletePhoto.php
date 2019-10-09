<?php
header('Content-Type: application/json');

require_once('../lib/db.php');
require_once('../lib/config.php');

if (empty($_POST['file']) || !preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file'])) {
    die(json_encode([
        'error' => 'No or invalid file provided',
    ]));
}

$file = $_POST['file'];
$filePath = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filePathThumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

if (!unlink($filePath) || !unlink($filePathThumb)) {
    die(json_encode([
        'error' => 'Could not delete file',
    ]));
}

unset($images[array_search($file, $images)]);
file_put_contents(__DIR__ . '/../data/db.txt', json_encode($images));

echo json_encode([
    'success' => true,
]);