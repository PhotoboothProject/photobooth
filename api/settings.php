<?php

use Photobooth\Environment;

require_once '../lib/boot.php';

header('Content-Type: application/javascript');

// Override secret configuration we don't need access from javascript for
$config['mail']['password'] = 'secret';
$config['login']['username'] = 'secret';
$config['login']['password'] = 'secret';
$config['login']['pin'] = 'secret';
$config['ftp']['username'] = 'secret';
$config['ftp']['password'] = 'secret';

echo 'const config = ' . json_encode($config) . ';';
echo 'const environment = ' . json_encode(new Environment()) . ';';
