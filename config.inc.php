<?php

$config = Array();

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'images';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['print'] = 'print';

// TAKE_PICTURE
// fixme: automatically react to the OS environment and set the appropriate commands and messages. also change takePic.php:17
$config['os'] = (DIRECTORY_SEPARATOR == '\\') || (strtolower(substr(PHP_OS, 0, 3)) === 'win') ? 'windows' : 'linux';
// the command to take a picture under linux and the appropriate confirmation message, if the picture was successfully taken
$config['take_picture']['linux']['cmd'] = 'sudo gphoto2 --capture-image-and-download --filename=%s images';
$config['take_picture']['linux']['msg'] = 'New file is in location';
// the command to take a picture under windows and the appropriate confirmation message, if the picture was successfully taken
$config['take_picture']['windows']['cmd'] = 'digicamcontrol\CameraControlCmd.exe /capture /filename %s';
$config['take_picture']['windows']['msg'] = 'Photo transfer done.';

// GALLERY
// should the gallery list the newest pictures first?
$config['gallery']['newest_first'] = true;

// DON'T MODIDY
// preparation
foreach($config['folders'] as $directory) {
    if(!is_dir($directory)){
        mkdir($directory, 0777);
    }
}