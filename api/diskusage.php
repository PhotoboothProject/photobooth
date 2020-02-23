<?php
header('Content-Type: application/json');

require_once('../lib/config.php');
require_once('../lib/hzip.php');

$data = $_POST;
$file = date('Ymd').'.zip';

if (!isset($data['type'])) {
    echo json_encode('error');
}

if ($data['type'] == 'zip') {
    $zipOut = $config['foldersAbs']['archives'] . '/' . $file;

    HZip::zipDir($config['foldersAbs']['data'], $zipOut);

    echo json_encode([
        'success' => 'zip',
        'file' => $file,
    ]);
}
