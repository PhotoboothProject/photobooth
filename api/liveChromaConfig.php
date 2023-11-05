<?php

require_once '../lib/boot.php';

use Photobooth\Service\LoggerService;

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

$logger = LoggerService::getInstance();
$logger->debug(basename($_SERVER['PHP_SELF']), $data);

echo json_encode($data);
exit();
