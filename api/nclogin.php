<?php
require_once '../lib/config.php';

if (!isset($_POST['url'])) {
    http_response_code(400);
    echo 'URL is not provided.', 'code';
    exit();
}

$url = $_POST['url']; // Retrieve the URL from the client

// Login Flow v2 request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url . '/index.php/login/v2',
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => ['OCS-APIRequest: true'],
]);
$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($loginHttpCode != 200) {
    http_response_code(500);
    echo 'Failed to make a request to the Nextcloud Login Flow v2 API.';
    exit();
}

// On success, return login URL
echo $loginResponse;
?>

