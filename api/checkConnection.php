<?php
header('Content-Type: application/json');

function is_connected() {
    $connected = @fsockopen('www.startpage.com', 80);
    if ($connected) {
        $is_conn = true;
        fclose($connected);
    } else {
        $is_conn = false;
    }

    return $is_conn;
}

if (is_connected()) {
    echo json_encode([
        'connected' => true,
    ]);
} else {
    echo json_encode([
        'connected' => false,
    ]);
}
