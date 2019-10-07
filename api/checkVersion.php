<?php
header('Content-Type: application/json');

$url = 'https://api.github.com/repos/andreknieriem/photobooth/releases/latest';

$options = [
    'http'=> [
        'method' => 'GET',
        'header' => "User-Agent: andreknieriem/photobooth\r\n"
    ]
];

$context = stream_context_create($options);
$content = file_get_contents($url, false, $context);
$data = json_decode($content, true);
$remoteVersion = substr($data['tag_name'], 1);

$packageContent = file_get_contents('../package.json');
$package = json_decode($packageContent, true);
$localVersion = $package['version'];

echo json_encode([
    'updateAvailable' => $remoteVersion !== $localVersion,
    'currentVersion' => $localVersion,
    'availableVersion' => $remoteVersion,
]);
