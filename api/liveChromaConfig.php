<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

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
$LogData = [
    'cmd' => $cmd,
    'isRunning' => true,
    'pid' => intval(1),
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
die($LogString);
