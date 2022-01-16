<?php
header('Content-Type: application/json');

$operating_system = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';

echo json_encode([
    'os' => $operating_system,
]);
