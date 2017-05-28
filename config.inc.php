<?php

$config = Array();

// folders
$config['folders']['images'] = 'images';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['print'] = 'print';

// preparation
foreach($config['folders'] as $directory) {
    if(!is_dir($directory)){
        mkdir($directory, 0777);
    }
}

