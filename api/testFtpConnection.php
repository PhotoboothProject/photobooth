<?php
header('Content-Type: application/json');

require_once '../lib/log.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

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
$ftp = ftp_ssl_connect($baseUrl, $port);

// login to ftp server
$login_result = ftp_login($ftp, $username, $password);

if (!$login_result) {
    $ErrorData = [
        'error' => "Can't connect to FTP Server!",
    ];

    $Logger->logToFile($ErrorData);

    $result['message'] = 'ftp:no_connection';
    die(json_encode($result));
}
ftp_close($ftp);
$result['response'] = 'success';
$result['message'] = 'ftp:connected';
die(json_encode($result));
