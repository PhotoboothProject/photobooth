<?php
header('Content-Type: application/json');

$os = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';

echo json_encode([
    'success' => $os,
]);
