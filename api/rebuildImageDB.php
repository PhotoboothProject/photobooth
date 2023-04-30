<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';

$database = new DatabaseManager(DB_FILE, IMG_DIR);
die(json_encode($database->rebuildDB()));
