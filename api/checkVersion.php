<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

function getLatestRelease(array $config): string {
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
    if (!$content) {
        throw new Exception('Failed to fetch data from API.');
    }
    $data = json_decode($content, true);
    if (!$data) {
        throw new Exception('Failed to parse JSON data.');
    }
    $remote_version = substr($data['tag_name'], 1);
    return $remote_version;
}

function getLogData(string $remote_version, string $local_version): array {
    return [
        'update_available' => $remote_version !== $local_version,
        'current_version' => $local_version,
        'available_version' => $remote_version,
        'php_script' => basename($_SERVER['PHP_SELF']),
    ];
}

try {
    $remote_version = getLatestRelease($config);
    $local_version = $config['photobooth']['version'];
    $log_data = getLogData($remote_version, $local_version);
    $log_string = json_encode($log_data);
    if ($config['dev']['loglevel'] > 0) {
        logError($log_data);
    }
    die($log_string);
} catch (Exception $e) {
    logError($e->getMessage());
    die(json_encode(['error' => $e->getMessage()]));
}
