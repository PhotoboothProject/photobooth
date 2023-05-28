<?php
require_once '../lib/config.php';

$jsonInput = file_get_contents('php://input');

if ($jsonInput === false) {
    error_log('Error reading input');
    http_response_code(500);
    echo 'Error reading input';
    exit();
}

$data = json_decode($jsonInput, true);

if ($data === null) {
    error_log('Error decoding JSON: ' . json_last_error_msg());
    http_response_code(400);
    echo 'Error decoding JSON: ' . json_last_error_msg();
    exit();
}

if (!isset($data['poll']['token']) || !isset($data['poll']['endpoint'])) {
    error_log("Invalid input: 'poll.token' and 'poll.endpoint' are required");
    http_response_code(400);
    echo "Invalid input: 'poll.token' and 'poll.endpoint' are required";
    exit();
}

$pollEndpoint = $data['poll']['endpoint'];
$token = $data['poll']['token'];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $pollEndpoint,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'token=' . $token,
    CURLOPT_RETURNTRANSFER => true,
]);

$startTime = time();
$statusCode = 404;
while ($statusCode == 404 && time() - $startTime < 1200) {
    // Poll for up to 1200 seconds (20 minutes)
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    sleep(5); // Wait for 5 seconds before the next poll
}

curl_close($ch);

if ($statusCode == 200) {
    $responseData = json_decode($response, true);
    error_log($response);
    if (isset($responseData['loginName']) && isset($responseData['appPassword'])) {
        $config['nextcloud']['user'] = $responseData['loginName'];
        $config['nextcloud']['pass'] = $responseData['appPassword'];
        echo $response;
    } else {
        http_response_code(500);
        echo "Response does not contain 'loginName' and 'appPassword'.";
    }
} else {
    http_response_code(500);
    echo 'Failed to make a request to the Nextcloud Login Flow v2 API or time limit exceeded.';
}
?>
