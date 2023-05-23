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
];

if ($config['dev']['loglevel'] > 1) {
    $Logger = new DataLogger(PHOTOBOOTH_LOG);
    $Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
    $Logger->addLogData($LogData);
    $Logger->logToFile();
}
$LogString = json_encode($LogData);
echo $LogString;
exit();
