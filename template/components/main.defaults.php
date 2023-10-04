<?php

use Photobooth\DatabaseManager;

if (isset($photoswipe) && $photoswipe) {
    $database = new DatabaseManager();
    $database->db_file = DB_FILE;
    $database->file_dir = IMG_DIR;
    if ($config['database']['enabled']) {
        $images = $database->getContentFromDB();
    } else {
        $images = $database->getFilesFromDirectory();
    }
    $imagelist = $config['gallery']['newest_first'] === true && !empty($images) ? array_reverse($images) : $images;
    if (isset($randomImage) && $randomImage && !empty($imagelist)) {
        shuffle($imagelist);
    }
}
