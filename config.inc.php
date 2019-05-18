<?php

$config = array();
$sys['os'] = (DIRECTORY_SEPARATOR == '\\') || (strtolower(substr(PHP_OS, 0, 3)) === 'win') ? 'windows' : 'linux';
$config['dev'] = true;
$config['use_print'] = true;
$config['use_qr'] = true;
$config['show_fork'] = true;
$config['previewFromCam'] = true; // experimental see https://github.com/andreknieriem/photobooth/pull/30
#$config['file_format'] = 'date'; // comment in to get dateformat images

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'images';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['print'] = 'print';

// GALLERY
// should the gallery list the newest pictures first?
$config['gallery']['newest_first'] = true;

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
	// foreach($config as $k=>$conf){
	// 	if(is_array($conf)) {
	// 		foreach($conf as $sk => $sc) {
	// 			if(isset($file[$k][$sk]) && !empty($file[$k][$sk])) {
	// 				$config[$k][$sk] = $file[$k][$sk];
	// 			}
	// 		}
	// 	} else {
	// 		if(isset($file[$k]) && !empty($file[$k])) {
	// 			$config[$k] = $file[$k];
	// 		}
	// 	}
	// }
}
