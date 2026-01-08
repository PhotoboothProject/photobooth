<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Utility\AdminKeypad;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');
checkCsrfOrFail($_POST);

// KEYPAD LOGIN
if (isset($_POST['controller']) and $_POST['controller'] == 'keypadLogin') {
    $now = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $throttleFile = PathUtility::getAbsolutePath('var/run/login_throttle.json');
    $windowSeconds = 300; // 5 minutes
    $maxAttempts = 10;

    $ipAttempts = ['count' => 0, 'window' => $now];
    if (is_readable($throttleFile)) {
        $raw = file_get_contents($throttleFile);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded) && isset($decoded[$ip]) && is_array($decoded[$ip])) {
            $ipAttempts = $decoded[$ip];
        }
    }
    if (($now - ($ipAttempts['window'] ?? 0)) > $windowSeconds) {
        $ipAttempts = ['count' => 0, 'window' => $now];
    }

    $blocked = $ipAttempts['count'] >= $maxAttempts;
    $state = false;
    if (!$blocked) {
        $state = AdminKeypad::login($_POST['pin'] ?? '', $config['login']);
        if ($state === true) {
            $ipAttempts = ['count' => 0, 'window' => $now];
        } else {
            $ipAttempts['count']++;
            usleep(300000); // 0.3s delay
            if ($ipAttempts['count'] >= $maxAttempts) {
                $blocked = true;
            }
        }
    }

    // Persist IP attempts
    $allAttempts = [];
    if (is_readable($throttleFile)) {
        $raw = file_get_contents($throttleFile);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            $allAttempts = $decoded;
        }
    }
    $allAttempts[$ip] = $ipAttempts;
    @file_put_contents($throttleFile, json_encode($allAttempts), LOCK_EX);

    $retryAfter = max(0, $windowSeconds - ($now - ($ipAttempts['window'] ?? 0)));

    $data = [
        'state'       => !$blocked && $state === true,
        'blocked'     => $blocked,
        'retry_after' => $blocked ? $retryAfter : 0,
    ];
    if ($blocked) {
        $data['message'] = 'Too many attempts. Please wait ' . $retryAfter . ' seconds.';
    }
    echo json_encode($data);
    exit();
}
