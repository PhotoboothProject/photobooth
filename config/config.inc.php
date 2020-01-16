<?php

// WARNING!
// Do not modify this file directly. Add your changes to my.config.inc.php

$config = array();
$config['dev'] = false;
$config['show_error_messages'] = true;
$config['use_print'] = false;
$config['use_qr'] = true;
$config['use_download'] = true;
$config['print_qrcode'] = true;
$config['print_frame'] = false;
$config['print_frame_path'] = '../resources/img/frames/frame.png';
$config['crop_onprint'] = false;
$config['crop_width'] = '1000';
$config['crop_height'] = '500';
$config['use_mail'] = false; // mail data needs to be configured
$config['show_fork'] = true;
$config['previewFromCam'] = false; // experimental see https://github.com/andreknieriem/photobooth/pull/30
$config['previewCamTakesPic'] = false; // HTTPS required to use tablet- or mobile phone camera
$config['previewCamFlipHorizontal'] = true;
$config['videoWidth'] = '1280';
$config['videoHeight'] = '720';
$config['camera_mode'] = 'user'; // possible values are "user" or "environment"
$config['cups_button'] = false;
$config['file_format_date'] = false;
$config['cntdwn_time'] = '5'; // control countdown timer
$config['collage_cntdwn_time'] = '3'; // control countdown timer between collage pictures
$config['cheese_time'] = '1000'; // control time for cheeeeese!
$config['use_filter'] = true;
$config['default_imagefilter'] = 'plain';
$config['disabled_filters'] = array();
$config['polaroid_effect'] = false;
$config['polaroid_rotation'] = '0';
$config['take_frame'] = false;
$config['take_frame_path'] = '../resources/img/frames/frame.png';
$config['chroma_keying'] = true;
$config['use_collage'] = false;
$config['continuous_collage'] = false;
$config['background_image'] = null;
$config['background_admin'] = null;
$config['background_chroma'] = null;
$config['force_buzzer'] = false;
$config['webserver_ip'] = null;
$config['rounded_corners'] = false;

// specify key id to use that key to take a picture or collage (e.g. 13 is the enter key)
// use for example https://keycode.info to get the key code
$config['photo_key'] = null;
$config['collage_key'] = null;

// LANGUAGE
// possible values: de, en, es, fr, gr
$config['language'] = 'en';

// StartScreen
$config['start_screen_title'] = 'Photobooth';
$config['start_screen_subtitle'] = 'Webinterface by André Rinas';

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'data/images';
$config['folders']['keying'] = 'data/keying';
$config['folders']['print'] = 'data/print';
$config['folders']['qrcodes'] = 'data/qrcodes';
$config['folders']['thumbs'] = 'data/thumbs';
$config['folders']['tmp'] = 'data/tmp';
$config['folders']['data'] = 'data';

// Event SETTINGS
$config['is_event'] = false;
$config['event']['textLeft'] = 'Name 1';
$config['event']['textRight'] = 'Name 2';
$config['event']['symbol'] = 'fa-heart-o';

// LOGIN
$config['login_enabled'] = false;
$config['login_username'] = 'Photo';
$config['login_password'] = NULL;
$config['protect_admin'] = true;
$config['protect_index'] = false;

// User Interface
$config['font_size'] = '16px';

// COLORS
$config['colors']['primary'] = '#e67e22';
$config['colors']['secondary'] = '#d35400';
$config['colors']['font'] = '#000000';
$config['colors']['button_font'] = '#ffffff';
$config['colors']['start_font'] = '#ffffff';
$config['colors']['countdown'] = '#000000';
$config['colors']['background_countdown'] = '#ffffff';
$config['colors']['cheese'] = '#000000';
$config['colors']['panel'] = '#ffffff';
$config['colors']['hover_panel'] = '#fafafa';
$config['colors']['border'] = '#eeeeee';
$config['colors']['box'] = '#f8f9fc';
$config['colors']['gallery_button'] = '#ffffff';

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
$config['font_path'] = '../resources/fonts/GreatVibes-Regular.ttf';
$config['fontsize'] = '100';
$config['linespace'] = '100';

// EMAIL
// If connection fails some help can be found here: https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting
// Especially gmail needs some special config
$config['send_all_later'] = false; // if true enables checkbox to save the current mail address for later in data/mail-addresses.txt
$config['mail_host'] = 'smtp.example.com';
$config['mail_username'] = 'photobooth@example.com';
$config['mail_password'] = 'yourpassword';
$config['mail_secure'] = 'tls';
$config['mail_port'] = '587';
$config['mail_fromAddress'] = 'photobooth@example.com';
$config['mail_fromName'] = 'Photobooth';
$config['mail_subject'] = null; 	// if empty, default translation is used
$config['mail_text'] = null;		// if empty, default translation is used

$config['take_picture']['cmd'] = null;
$config['take_picture']['msg'] = null;
$config['print']['cmd'] = null;
$config['print']['msg'] = null;

$config['jpeg_quality_thumb'] = 60;
$config['jpeg_quality_chroma'] = 70;
$config['jpeg_quality_image'] = 80;

// RESET
$config['reset_remove_images'] = true;
$config['reset_remove_mailtxt'] = true;
$config['reset_remove_config'] = true;
