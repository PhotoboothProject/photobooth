<?php

require_once '../lib/boot.php';

use Photobooth\Service\DatabaseManagerService;

header('Content-Type: application/json');

$database = DatabaseManagerService::getInstance();
echo json_encode($database->rebuildDB());
exit();
