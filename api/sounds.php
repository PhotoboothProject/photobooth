<?php

require_once '../lib/boot.php';

use Photobooth\Service\SoundService;

$soundService = SoundService::getInstance();
header('Content-Type: application/json');
echo json_encode($soundService->all());

exit();
