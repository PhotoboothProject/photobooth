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
$config['print_qrcode'] = true;
$config['print_frame'] = false;
$config['use_mail'] = false; // mail data needs to be configured
$config['use_gpio_button'] = false; // Use alt+p to take a new picture, can be triggered via GPIO24
$config['show_fork'] = true;
$config['previewFromCam'] = false; // experimental see https://github.com/andreknieriem/photobooth/pull/30
$config['file_format_date'] = false;
$config['cntdwn_time'] = '5'; // control countdown timer
$config['cheese_time'] = '1000'; // control time for cheeeeese!
$config['use_filter'] = true;
$config['polaroid_effect'] = false;
$config['polaroid_rotation'] = '0';
$config['chroma_keying'] = true;
$config['use_collage'] = false;
$config['bluegray_theme'] = false;

// LANGUAGE
// possible values: en, de, es, fr
$config['language'] = 'de';

// StartScreen
$config['start_screen_title'] = 'Photobooth';
$config['start_screen_subtitle'] = 'Webinterface by André Rinas';

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'images';
$config['folders']['keying'] = 'keying';
$config['folders']['print'] = 'print';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['tmp'] = 'tmp';

// WEDDING SETTINGS
$config['is_wedding'] = false;
$config['wedding']['groom'] = 'Name 1';
$config['wedding']['bride'] = 'Name 2';
$config['wedding']['symbol'] = 'fa-heart-o';

// GALLERY
// should the gallery list the newest pictures first?
$config['show_gallery'] = true;
$config['newest_first'] = true;
$config['scrollbar'] = false;
$config['show_date'] = false; // only works if file_format_date = true
$config['gallery']['date_format'] = 'd.m.Y - G:i';

// TEXT ON PRINT
$config['is_textonprint'] = false;
$config['textonprint']['line1'] = 'line 1';
$config['textonprint']['line2'] = 'line 2';
$config['textonprint']['line3'] = 'line 3';
$config['locationx'] = '2250';
$config['locationy'] = '1050';
$config['rotation'] = '40';
$config['fontsize'] = '100';
$config['linespace'] = '100';

// EMAIL
// If connection fails some help can be found here: https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting
// Especially gmail needs some special config
$config['send_all_later'] = false; // if true enables checkbox to save the current mail address for later in mail-addresses.txt
$config['mail_host'] = 'smtp.example.com';
$config['mail_username'] = 'photobooth@example.com';
$config['mail_password'] = 'yourpassword';
$config['mail_secure'] = 'tls';
$config['mail_port'] = '587';
$config['mail_fromAddress'] = 'photobooth@example.com';
$config['mail_fromName'] = 'Photobooth';

switch($config['language']) {
	case 'de':
	$config['mail_subject'] = 'Hier ist dein Bild';
	$config['mail_text'] = 'Hey, dein Bild ist angehangen.';
	break;
	case 'fr':
	$config['mail_subject'] = 'Voici votre photo';
	$config['mail_text'] = 'Hé, ta photo est attachée.';
	break;
	case 'en':
	default:
	$config['mail_subject'] = 'Here is your picture';
	$config['mail_text'] = 'Hey, your picture is attached.';
	break;
}

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
