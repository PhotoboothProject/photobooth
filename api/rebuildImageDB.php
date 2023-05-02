<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

die(json_encode($database->rebuildDB()));
