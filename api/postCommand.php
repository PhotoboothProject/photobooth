<?php
header('Content-Type: application/json');

require_once('../lib/config.php');

$post_cmd = sprintf($config['post_photo']['cmd']);
exec($post_cmd);

echo json_encode([
    'success' => 'true',
]);
