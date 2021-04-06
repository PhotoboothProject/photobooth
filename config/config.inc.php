<?php

// WARNING!
// Do not modify this file directly.
// Please use the admin panel (http://localhost/admin) to change your personal configuration.
//
$config = array();

// G E N E R A L
// possible language values: de, el, en, es, fr, pl, it
$config['ui']['language'] = 'en';
$config['adminpanel']['view'] = 'basic';
$config['dev']['enabled'] = false;
$config['start_screen']['title'] = 'Photobooth';
$config['start_screen']['title_visible'] = true;
$config['start_screen']['subtitle'] = NULL;
$config['start_screen']['subtitle_visible'] = true;
// possible thumb_size values: '360px', '540px', '900px', '1080px', '1260px'
$config['picture']['thumb_size'] = '540px';
$config['dev']['error_messages'] = true;
$config['dev']['reload_on_error'] = true;
$config['qr']['enabled'] = true;
$config['webserver']['ip'] = '127.0.0.1';
$config['webserver']['ssid'] = 'Photobooth';
$config['download']['enabled'] = true;
$config['download']['thumbs'] = false;
// control time in milliseconds until Photobooth reloads automatically
$config['picture']['time_to_live'] = '90000';
$config['picture']['preview_before_processing'] = false;
$config['delete']['no_request'] = false;
$config['database']['enabled'] = true;
$config['database']['file'] = 'db';


// F R O N T P A G E
$config['ui']['show_fork'] = true;
$config['event']['enabled'] = true;
$config['event']['textLeft'] = 'We';
// possible event symbol values: 'fa-camera-retro', 'fa-birthday-cake', 'fa-gift', 'fa-tree', 'fa-snowflake-o', 'fa-heart-o', 
//                               'fa-heart', 'fa-heartbeat', 'fa-apple', 'fa-anchor', 'fa-glass', 'fa-gears', 'fa-users'
$config['event']['symbol'] = 'fa-heart-o';
$config['event']['textRight'] = 'OpenSource';
$config['button']['force_buzzer'] = false;


// P I C T U R E S
// control countdown timer in seconds
$config['picture']['cntdwn_time'] = '5';
$config['picture']['no_cheese'] = false;
// control time for cheeeeese! in milliseconds
$config['picture']['cheese_time'] = '1000';
// possible flip values: 'off', 'horizontal', 'vertical', 'both'
$config['picture']['flip'] = 'off';
$config['picture']['rotation'] = '0';
$config['picture']['polaroid_effect'] = false;
$config['picture']['polaroid_rotation'] = '0';
$config['filters']['enabled'] = true;
$config['filters']['defaults'] = 'plain';
$config['filters']['disabled'] = array();
$config['picture']['take_frame'] = false;
$config['picture']['frame'] = 'resources/img/frames/frame.png';
// specify key id (e.g. 13 is the enter key) to use that key to take a picture (picture key)
// use for example https://keycode.info to get the key code
$config['picture']['key'] = null;
// possible naming values: 'dateformatted', 'numbered', 'random'
$config['picture']['naming'] = 'dateformatted';
// permissions example values: '0644' (rw-r--r--), '0666' (rw-rw-rw-), '0600' (rw-------)
$config['picture']['permissions'] = '0644';
$config['picture']['keep_original'] = true;
$config['picture']['preserve_exif_data'] = false;
$config['picture']['allow_delete'] = true;
$config['textonpicture']['enabled'] = false;
$config['textonpicture']['line1'] = 'line 1';
$config['textonpicture']['line2'] = 'line 2';
$config['textonpicture']['line3'] = 'line 3';
$config['textonpicture']['locationx'] = '80';
$config['textonpicture']['locationy'] = '80';
$config['textonpicture']['rotation'] = '0';
$config['textonpicture']['font'] = 'resources/fonts/GreatVibes-Regular.ttf';
// possible font_color values: 'white', 'grey', 'black'
$config['textonpicture']['font_color'] = 'white';
$config['textonpicture']['font_size'] = '80';
$config['textonpicture']['linespace'] = '90';


// C O L L A G E
$config['collage']['enabled'] = true;
$config['collage']['only'] = false;
// control countdown timer between collage pictures in seconds
$config['collage']['cntdwn_time'] = '3';
$config['collage']['continuous'] = true;
// possible layout values: '2x2', '2x2-2', '2x4', '2x4-2', '1+3', '1+2'
$config['collage']['layout'] = '2x2-2';
// specify key id (e.g. 13 is the enter key) to use that key to take a collage (collage key)
// use for example https://keycode.info to get the key code
$config['collage']['key'] = null;
// possible take_frame values: 'off', 'always', 'once'
$config['collage']['take_frame'] = 'off';
$config['collage']['frame'] = 'resources/img/frames/frame.png';
$config['textoncollage']['enabled'] = true;
$config['textoncollage']['line1'] = 'Photobooth';
$config['textoncollage']['line2'] = '   we love';
$config['textoncollage']['line3'] = 'OpenSource';
$config['textoncollage']['locationx'] = '1470';
$config['textoncollage']['locationy'] = '250';
$config['textoncollage']['rotation'] = '0';
$config['textoncollage']['font'] = 'resources/fonts/GreatVibes-Regular.ttf';
// possible font_color values: 'white', 'grey', 'black'
$config['textoncollage']['font_color'] = 'black';
$config['textoncollage']['font_size'] = '50';
$config['textoncollage']['linespace'] = '90';
// DO NOT CHANGE limit here
$config['collage']['limit'] = NULL;


// G A L L E R Y
$config['gallery']['enabled'] = true;
$config['gallery']['newest_first'] = true;
$config['gallery']['use_slideshow'] = true;
$config['gallery']['pictureTime'] = '3000';
$config['pswp']['animateTransitions'] = false;
$config['pswp']['fullscreenEl'] = false;
$config['pswp']['counterEl'] = true;
$config['pswp']['history'] = true;
// show_date only works if picture naming  = 'dateformatted'
$config['gallery']['show_date'] = true;
$config['gallery']['date_format'] = 'd.m.Y - G:i';
$config['gallery']['db_check_enabled'] = true;
$config['gallery']['db_check_time'] = '10';
$config['gallery']['allow_delete'] = true;
$config['gallery']['scrollbar'] = false;
$config['gallery']['bottom_bar'] = true;
$config['pswp']['clickToCloseNonZoomable'] = false;
$config['pswp']['closeOnScroll'] = false;
$config['pswp']['closeOnOutsideClick'] = false;
$config['pswp']['preventSwiping'] = false;
$config['pswp']['pinchToClose'] = true;
$config['pswp']['closeOnVerticalDrag'] = true;
$config['pswp']['tapToToggleControls'] = true;
$config['pswp']['zoomEl'] = false;
$config['pswp']['loop'] = true;
$config['pswp']['bgOpacity'] = 1;


// P R E V I E W
// Please read https://github.com/andi34/photobooth/wiki/FAQ#how-to-use-a-live-stream-as-background-at-countdown
// possible preview_mode values: none, device_cam, url, gphoto
$config['preview']['mode'] = 'none';
$config['preview']['gphoto_bsm'] = true;
$config['preview']['camTakesPic'] = false;
$config['preview']['flipHorizontal'] = true;
// possible rotation values: '0deg', '90deg', -90deg', '180deg', '45deg', '-45deg'
$config['preview']['rotation'] = '0deg';
$config['preview']['url'] = null;
$config['preview']['videoWidth'] = '1280';
$config['preview']['videoHeight'] = '720';
// possible camera_mode values: "user", "environment"
$config['preview']['camera_mode'] = 'user';
$config['preview']['asBackground'] = false;


// K E Y I N G
$config['keying']['enabled'] = false;
// possible size values: '1000px', '1500px', '2000px', '2500px'
$config['keying']['size'] = '1500px';
$config['live_keying']['enabled'] = false;
// possible variant values: 'marvinj', 'seriouslyjs'
$config['keying']['variant'] = 'seriouslyjs';
$config['keying']['seriouslyjs_color'] = '#62af74';
$config['keying']['background_path'] = 'resources/img/background';
$config['live_keying']['show_all'] = false;


// P R I N T
$config['button']['show_cups'] = false;
$config['print']['from_result'] = false;
$config['print']['from_gallery'] = false;
$config['print']['from_chromakeying'] = false;
$config['print']['auto'] = false;
$config['print']['auto_delay'] = '1000';
$config['print']['time'] = '5000';
$config['print']['key'] = null;
$config['print']['qrcode'] = false;
$config['print']['print_frame'] = false;
$config['print']['frame'] = 'resources/img/frames/frame.png';
$config['print']['crop'] = false;
$config['print']['crop_width'] = '1000';
$config['print']['crop_height'] = '500';
$config['textonprint']['enabled'] = false;
$config['textonprint']['line1'] = 'line 1';
$config['textonprint']['line2'] = 'line 2';
$config['textonprint']['line3'] = 'line 3';
$config['textonprint']['locationx'] = '2250';
$config['textonprint']['locationy'] = '1050';
$config['textonprint']['rotation'] = '40';
$config['textonprint']['font'] = 'resources/fonts/GreatVibes-Regular.ttf';
// possible font_color values: 'white', 'grey', 'black'
$config['textonprint']['font_color'] = 'black';
$config['textonprint']['font_size'] = '100';
$config['textonprint']['linespace'] = '100';


// E -  M A I L
// Please read https://github.com/andi34/photobooth/wiki/FAQ#ive-trouble-setting-up-e-mail-config-how-do-i-solve-my-problem
//
// If send_all_later is enabled, a checkbox to save the current mail address for later in {mail_file}.txt is visible
$config['mail']['enabled'] = false;
$config['mail']['send_all_later'] = false;
$config['mail']['subject'] = null; 	// if empty, default translation is used
$config['mail']['text'] = null;		// if empty, default translation is used
$config['mail']['host'] = 'smtp.example.com';
$config['mail']['username'] = 'photobooth@example.com';
$config['mail']['password'] = 'yourpassword';
$config['mail']['fromAddress'] = 'photobooth@example.com';
$config['mail']['fromName'] = 'Photobooth';
$config['mail']['file'] = 'mail-adresses';
$config['mail']['secure'] = 'tls';
$config['mail']['port'] = '587';


// S T A N D A L O N E   S L I D E S H O W
$config['slideshow']['refreshTime'] = '60';
$config['slideshow']['pictureTime'] = '3000';
$config['slideshow']['randomPicture'] = true;
$config['slideshow']['use_thumbs'] = false;


// R E M O T E   B U Z Z E R
$config['remotebuzzer']['enabled'] = false;
$config['remotebuzzer']['userotary'] = false;
$config['remotebuzzer']['picturebutton'] = true;
// collagetime controls the time to distinguish picture from collage in seconds
$config['remotebuzzer']['collagetime'] = '2';
$config['remotebuzzer']['picturegpio'] = 21;
$config['remotebuzzer']['collagebutton'] = false;
$config['remotebuzzer']['collagegpio'] = 20;
$config['remotebuzzer']['printbutton'] = false;
$config['remotebuzzer']['printgpio'] = 26;
$config['remotebuzzer']['shutdownbutton'] = false;
$config['remotebuzzer']['shutdowngpio'] = 16;
$config['remotebuzzer']['shutdownholdtime'] = '5';
$config['remotebuzzer']['port'] = 14711;


// S Y N C  T O  U S B  S T I C K
$config['synctodrive']['enabled'] = false;
$config['synctodrive']['target'] = 'photobooth'; //Default target for the sync script
$config['synctodrive']['interval'] = 300;


// A U T H E N T I C A T I O N
$config['login']['enabled'] = false;
$config['login']['username'] = 'Photo';
$config['login']['password'] = NULL;
$config['protect']['admin'] = true;
$config['protect']['localhost_admin'] = true;
$config['protect']['index'] = false;
$config['protect']['localhost_index'] = false;
$config['protect']['manual'] = false;
$config['protect']['localhost_manual'] = false;


// U S E R   I N T E R F A C E
// possible style values: "classic", "modern", "custom"
$config['ui']['style'] = 'modern';
$config['button']['show_fs'] = false;
$config['ui']['font_size'] = '16px';
$config['colors']['countdown'] = '#ffffff';
$config['colors']['background_countdown'] = '#214852';
$config['colors']['cheese'] = '#ffffff';
$config['background']['defaults'] = null;
$config['background']['admin'] = null;
$config['background']['chroma'] = null;
$config['ui']['decore_lines'] = true;
$config['ui']['rounded_corners'] = false;
$config['colors']['primary'] = '#0a6071';
$config['colors']['secondary'] = '#214852';
$config['colors']['font'] = '#79bad9';
$config['colors']['button_font'] = '#ffffff';
$config['colors']['start_font'] = '#ffffff';
$config['colors']['panel'] = '#2d4157';
$config['colors']['hover_panel'] = '#446781';
$config['colors']['border'] = '#eeeeee';
$config['colors']['box'] = '#f8f9fc';
$config['colors']['gallery_button'] = '#ffffff';


// J P E G   Q U A L I T Y
$config['jpeg_quality']['image'] = 100;
$config['jpeg_quality']['chroma'] = 100;
$config['jpeg_quality']['thumb'] = 60;


// C O M M A N D S
$config['take_picture']['cmd'] = null;
$config['take_picture']['msg'] = null;
$config['print']['cmd'] = null;
$config['print']['msg'] = null;
$config['exiftool']['cmd'] = null;
$config['exiftool']['msg'] = null;
$config['preview']['cmd'] = null;
$config['preview']['killcmd'] = null;
$config['nodebin']['cmd'] = null;
$config['pre_photo']['cmd'] = null;
$config['post_photo']['cmd'] = null;

// F O L D E R S
$config['folders']['data'] = 'data';
$config['folders']['images'] = 'images';
$config['folders']['keying'] = 'keying';
$config['folders']['print'] = 'print';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['tmp'] = 'tmp';
$config['folders']['archives'] = 'archives';


// R E S E T
$config['reset']['remove_images'] = true;
$config['reset']['remove_mailtxt'] = false;
$config['reset']['remove_config'] = false;
