<?php

//  WARNING!
// This config gets overwritten by the admin panel if you use it.
// If you want to use only this file, delete the admin/config.json file and do not use the admin panel
// as it writes new config.json files.

$config = array();
$sys['os'] = (DIRECTORY_SEPARATOR == '\\') || (strtolower(substr(PHP_OS, 0, 3)) === 'win') ? 'windows' : 'linux';
$config['dev'] = false;
$config['use_print'] = false;
$config['use_qr'] = true;
$config['show_fork'] = true;
$config['previewFromCam'] = false; // experimental see https://github.com/andreknieriem/photobooth/pull/30
$config['file_format_date'] = false;
$config['cntdwn_time'] = '5'; // control countdown timer
$config['cheese_time'] = '1000'; // control time for cheeeeese!

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'images';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['print'] = 'print';

// GALLERY
// should the gallery list the newest pictures first?
$config['gallery']['show_gallery'] = true;
$config['gallery']['newest_first'] = true;
$config['gallery']['scrollbar'] = false;
$config['gallery']['show_date'] = false; // only works if file_format_date = true
$config['gallery']['date_format'] = 'd.m.Y - G:i';

// LANGUAGE
// possible values: en, de, fr
$config['language'] = 'de';

// COMMANDS and MESSAGES
switch($sys['os']) {
	case 'windows':
	$config['take_picture']['cmd'] = 'digicamcontrol\CameraControlCmd.exe /capture /filename %s';
	$config['take_picture']['msg'] = 'Photo transfer done.';
	$config['print']['cmd'] = 'mspaint /pt "%s"';
	$config['print']['msg'] = '';
	break;
	case 'linux':
	default:
	$config['take_picture']['cmd'] = 'sudo gphoto2 --capture-image-and-download --filename=%s images';
	$config['take_picture']['msg'] = 'New file is in location';
	$config['print']['cmd'] = 'sudo lp -o landscape -o fit-to-page %s';
	$config['print']['msg'] = '';
	break;
}

// MERGE WITH admin/config.json if exists
$filename = false;
if(file_exists('admin/config.json')) {
	$filename = 'admin/config.json';
} elseif(file_exists('config.json')) {
	$filename = 'config.json';
}

if($filename){
	$file = json_decode(file_get_contents($filename),true);
	$config = $file;
}
