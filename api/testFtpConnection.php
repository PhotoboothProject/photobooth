<?php

require_once '../lib/boot.php';

use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$data = $_POST;

$params = ['baseURL', 'port', 'username', 'password'];

$result = [
    'response' => 'error',
    'message' => 'ftp:missing_parameters',
    'missing' => [],
];

foreach ($params as $param) {
    if ($data['ftp'][$param] == '') {
        $result['missing'][] = $param;
    }
}

if (!empty($result['missing'])) {
    die(json_encode($result));
}

$baseUrl = $data['ftp']['baseURL'];
$port = $data['ftp']['port'];
$username = $data['ftp']['username'];
$password = $data['ftp']['password'];

// init connection to ftp server
$ftp = ftp_ssl_connect($baseUrl, $port, 10);

// login to ftp server
$login_result = @ftp_login($ftp, $username, $password);
$result['response'] = 'success';
$result['message'] = 'ftp:connected';

if (!$login_result) {
    $logger->error('Can\'t connect to FTP Server!');
    $result['response'] = 'error';
    $result['message'] = 'ftp:no_connection';
}
@ftp_close($ftp);
die(json_encode($result));
