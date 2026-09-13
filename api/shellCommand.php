<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Service\LoggerService;

header('Content-Type: application/json');
checkCsrfOrFail($_POST);

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$mode = $_POST['mode'] ?? '';
$isAdmin = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$isRental = !empty($_SESSION['rental']);

if (empty($mode)) {
    $data = [
        'success' => 'false',
        'mode' => 'No mode defined.',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    die();
}

// Allow pre/post without admin auth; protect dangerous actions.
// If login is enabled, require auth for administrative modes (e.g., reboot/shutdown).
if (
    ($config['login']['enabled'] ?? false)
    && !$isAdmin
    && !($isRental && in_array($mode, ['reboot', 'shutdown'], true))
    && !in_array($mode, ['pre-command', 'post-command'], true)
) {
    $data = [
        'success' => 'false',
        'mode' => 'Unauthorized',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    die();
}

// Allow pre/post without admin auth; protect dangerous actions.
switch ($mode) {
    case 'pre-command':
        $cmd = sprintf($config['commands']['pre_photo']);
        break;
    case 'post-command':
        $filename = $_POST['filename'] ?? '';
        $filename = basename(str_replace(['\\', '/'], '_', $filename));
        if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            $data = [
                'success' => 'false',
                'mode' => 'Invalid filename',
            ];
            $logger->debug('message', $data);
            echo json_encode($data);
            die();
        }
        $cmd = sprintf($config['commands']['post_photo'], escapeshellarg($filename));
        break;
    case 'reboot':
    case 'shutdown':
        $sudoCmd = $mode === 'reboot' ? $config['commands']['reboot'] : $config['commands']['shutdown'];
        $cmd = 'sudo ' . sprintf($sudoCmd);
        break;
    default:
        $data = [
            'success' => 'false',
            'mode' => 'Unknown mode ' . $mode,
        ];
        $logger->debug('message', $data);
        echo json_encode($data);
        die();
}

exec($cmd . ' 2>&1', $output, $retval);
$data = [
    'success' => $retval === 0,
    'output' => $output,
    'retval' => $retval,
    'command' => $cmd,
];

$logger->debug('data', $data);
echo json_encode($data);
exit();
