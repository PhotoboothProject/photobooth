<?php
header('Content-Type: application/json');

require_once '../lib/config.php';

if (file_exists(PRINT_LOCKFILE)) {
    if (unlink(PRINT_LOCKFILE)) {
        echo json_encode('success');
    } else {
        echo json_encode('error');
    }
} else {
    echo json_encode('success');
}
