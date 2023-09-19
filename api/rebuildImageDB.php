<?php

require_once '../lib/boot.php';

use Photobooth\DatabaseManager;

header('Content-Type: application/json');

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

echo json_encode($database->rebuildDB());
exit();
