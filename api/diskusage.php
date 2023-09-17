<?php

require_once '../lib/boot.php';

use Photobooth\HZip;

header('Content-Type: application/json');

$data = $_POST;
$file = date('Ymd-Hi') . '.zip';

if (!isset($data['type'])) {
    echo json_encode('error');
}

if ($data['type'] == 'zip') {
    $source = $config['foldersAbs']['data'];
    $zipOut = $config['foldersAbs']['archives'] . DIRECTORY_SEPARATOR . $file;

    HZip::zipDir($source, $zipOut);

    echo json_encode([
        'success' => 'zip',
        'file' => $file,
    ]);
}
exit();
