<?php
header('Content-Type: application/javascript');

require '../lib/config.php';

// Override secret configuration we don't need access from javascript for
$config['mail']['password'] = 'secret';
$config['login']['username'] = 'secret';
$config['login']['password'] = 'secret';
$config['login']['pin'] = 'secret';
?>
const config = <?= json_encode($config) ?>;

