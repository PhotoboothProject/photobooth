<?php

use Photobooth\Service\DatabaseManagerService;

if (isset($photoswipe) && $photoswipe) {
    $database = DatabaseManagerService::getInstance();
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
