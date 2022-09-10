<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

$url = 'https://api.github.com/repos/' . $config['ui']['github'] . '/photobooth/releases/latest';
$gh = $config['ui']['github'];
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: $gh/photobooth\r\n",
    ],
];

$context = stream_context_create($options);
$content = file_get_contents($url, false, $context);
$data = json_decode($content, true);
$remoteVersion = substr($data['tag_name'], 1);

$packageContent = file_get_contents('../package.json');
$package = json_decode($packageContent, true);
$localVersion = $package['version'];

$LogData = [
    'updateAvailable' => $remoteVersion !== $localVersion,
    'currentVersion' => $localVersion,
    'availableVersion' => $remoteVersion,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 0) {
    logError($LogData);
}
die($LogString);
