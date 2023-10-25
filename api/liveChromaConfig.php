<?php

require_once '../lib/boot.php';

use Photobooth\DataLogger;

header('Content-Type: application/json');

$cmd = 'python3 cameracontrol.py';
if ($_POST['chromaImage']) {
    $cmd .= ' --chromaImage ' . escapeshellarg($_POST['chromaImage']);
}
if ($_POST['chromaColor']) {
    $cmd .= ' --chromaColor ' . escapeshellarg($_POST['chromaColor']);
}
if ($_POST['chromaSensitivity']) {
    $cmd .= ' --chromaSensitivity ' . escapeshellarg($_POST['chromaSensitivity']);
}
if ($_POST['chromaBlend']) {
    $cmd .= ' --chromaBlend ' . escapeshellarg($_POST['chromaBlend']);
}
exec($cmd);
$data = [
    'cmd' => $cmd,
    'isRunning' => true,
    'pid' => intval(1),
];

if ($config['dev']['loglevel'] > 1) {
    $logger = new DataLogger(PHOTOBOOTH_LOG);
    $logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
    $logger->addLogData($data);
    $logger->logToFile();
}

echo json_encode($data);
exit();
