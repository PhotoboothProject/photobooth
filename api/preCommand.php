<?php
header('Content-Type: application/json');

require_once('../lib/config.php');

$pre_cmd = sprintf($config['pre_photo']['cmd']);
exec($pre_cmd);

echo json_encode([
    'success' => 'true',
]);
