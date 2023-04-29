<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/helper.php';

function get_log_data($config) {
    try {
        $photobooth = new Photobooth();

        $log_data = [
            'update_available' => $photobooth->checkUpdate(),
            'current_version' => $photobooth->get_photobooth_version(),
            'available_version' => $photobooth->getLatestRelease(),
            'php_script' => basename($_SERVER['PHP_SELF']),
        ];

        if ($config['dev']['loglevel'] > 0) {
            logError($log_data);
        }
    } catch (Exception $e) {
        logError($e->getMessage());
        $log_data = ['error' => $e->getMessage()];
    }

    return $log_data;
}

$log_data = get_log_data($config);
$log_string = json_encode($log_data);
die($log_string);
