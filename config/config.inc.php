<?php

// WARNING!
// Do not modify this file directly.
// Please use the admin panel (http://localhost/admin) to change your personal configuration.
//
$config = array();

// G E N E R A L
// possible language values: de, en, fr, it
$config['ui']['language'] = 'en';
$config['ui']['folders_lang'] = '';
$config['adminpanel']['view'] = 'basic';
$config['dev']['loglevel'] = '1';
$config['dev']['demo_images'] = false;
$config['start_screen']['title'] = 'Photobooth';
$config['start_screen']['title_visible'] = true;
$config['start_screen']['subtitle'] = '';
$config['start_screen']['subtitle_visible'] = true;
// possible thumb_size values: '360px', '540px', '900px', '1080px', '1260px'
$config['picture']['thumb_size'] = '540px';
$config['dev']['reload_on_error'] = true;
$config['webserver']['ip'] = '';
$config['webserver']['ssid'] = 'Photobooth';
$config['download']['enabled'] = true;
$config['download']['thumbs'] = false;
// control time in seconds until Photobooth reloads automatically
$config['picture']['time_to_live'] = '90';
$config['picture']['preview_before_processing'] = false;
$config['picture']['retry_on_error'] = '0';
$config['picture']['retry_timeout'] = '2';
$config['delete']['no_request'] = false;
$config['database']['enabled'] = true;
$config['database']['file'] = 'db';


// F R O N T P A G E
$config['ui']['show_fork'] = true;
$config['ui']['skip_welcome'] = false;
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
$config['picture']['cntdwn_offset'] = '0';
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
$config['picture']['frame'] = '';
// specify key id (e.g. 13 is the enter key) to use that key to take a picture (picture key)
// use for example https://keycode.info to get the key code
$config['picture']['key'] = '';
// possible naming values: 'dateformatted', 'random'
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
$config['textonpicture']['font'] = '';
$config['textonpicture']['font_color'] = '#ffffff';
$config['textonpicture']['font_size'] = '80';
$config['textonpicture']['linespace'] = '90';


// C O L L A G E
$config['collage']['enabled'] = true;
$config['collage']['only'] = false;
// control countdown timer between collage pictures in seconds
$config['collage']['cntdwn_time'] = '3';
$config['collage']['continuous'] = true;
$config['collage']['continuous_time'] = '5';
// possible layout values: '2+2', '2+2-2', '1+3', '1+3-2', '3+1', '1+2', '2+1', '2x4', '2x4-2', '2x4-3', '2x3', 'collage.json'
$config['collage']['layout'] = '2+2-2';
// possible layout values: '150dpi', '300dpi', '400dpi', '600dpi'
$config['collage']['resolution'] = '300dpi';
$config['collage']['dashedline_color'] = '#000000';
$config['collage']['keep_single_images'] = false;
// specify key id (e.g. 13 is the enter key) to use that key to take a collage (collage key)
// use for example https://keycode.info to get the key code
$config['collage']['key'] = '';
$config['collage']['background_color'] = '#ffffff';
// possible take_frame values: 'off', 'always', 'once'
$config['collage']['take_frame'] = 'off';
$config['collage']['frame'] = '';
$config['collage']['placeholder'] = false;
$config['collage']['placeholderposition'] = '1';
$config['collage']['placeholderpath'] = '';
$config['collage']['background'] = '';
$config['textoncollage']['enabled'] = true;
$config['textoncollage']['line1'] = 'Photobooth';
$config['textoncollage']['line2'] = '   we love';
$config['textoncollage']['line3'] = 'OpenSource';
$config['textoncollage']['locationx'] = '1470';
$config['textoncollage']['locationy'] = '250';
$config['textoncollage']['rotation'] = '0';
$config['textoncollage']['font'] = '';
$config['textoncollage']['font_color'] = '#000000';
$config['textoncollage']['font_size'] = '50';
$config['textoncollage']['linespace'] = '90';
// DO NOT CHANGE limit here
$config['collage']['limit'] = '';


// V I D E O
$config['video']['enabled'] = false;
$config['video']['cntdwn_time'] = '3';
$config['video']['cheese'] = 'Show your moves!';
$config['video']['collage'] = false;
$config['video']['collage_keep_images'] = false;
$config['video']['collage_only'] = false;
$config['video']['effects'] = 'none';
$config['video']['animation'] = true;
$config['video']['gif'] = false;
$config['video']['qr'] = true;


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
$config['gallery']['figcaption'] = true;
$config['gallery']['action_footer'] = true;
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
// Please read https://photoboothproject.github.io/FAQ#how-to-use-a-live-stream-as-background-at-countdown
// possible preview_mode values: none, device_cam, url
$config['preview']['mode'] = 'none';
$config['preview']['bsm'] = true;
$config['preview']['stop_time'] = '2';
$config['preview']['camTakesPic'] = false;
$config['preview']['style'] = 'scale-down';
// possibile flip values: off, flip-horizontal, flip-vertical
$config['preview']['flip'] = 'off';
// possible rotation values: '0deg', '90deg', -90deg', '180deg', '45deg', '-45deg'
$config['preview']['rotation'] = '0deg';
$config['preview']['url'] = '';
$config['preview']['videoWidth'] = '1280';
$config['preview']['videoHeight'] = '720';
// possible camera_mode values: "user", "environment"
$config['preview']['camera_mode'] = 'user';
$config['preview']['asBackground'] = false;
$config['preview']['showFrame'] = false;


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
$config['print']['no_rotate'] = false;
$config['print']['key'] = '';
$config['print']['qrcode'] = false;
$config['print']['qrSize'] = '4';
$config['print']['qrPosition'] = 'bottomRight';
$config['print']['qrOffset'] = 10;
$config['print']['qrMargin'] = '4';
$config['print']['qrBgColor'] = '#ffffff';
$config['print']['print_frame'] = false;
$config['print']['frame'] = '';
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
$config['textonprint']['font'] = '';
$config['textonprint']['font_color'] = '#ffffff';
$config['textonprint']['font_size'] = '100';
$config['textonprint']['linespace'] = '100';


// Q R  -  C O D E
$config['qr']['enabled'] = true;
$config['qr']['ecLevel'] = 'QR_ECLEVEL_M';
$config['qr']['url'] = '';
$config['qr']['append_filename'] = true;
$config['qr']['custom_text'] = false;
$config['qr']['text'] = '';


// E -  M A I L
// Please read https://photoboothproject.github.io/FAQ#ive-trouble-setting-up-e-mail-config-how-do-i-solve-my-problem
//
// If send_all_later is enabled, a checkbox to save the current mail address for later in {mail_file}.txt is visible
$config['mail']['enabled'] = false;
$config['mail']['send_all_later'] = false;
$config['mail']['subject'] = ''; 	// if empty, default translation is used
$config['mail']['text'] = '';		// if empty, default translation is used
$config['mail']['alt_text'] = '';		// if empty, default translation is used
$config['mail']['is_html'] = false;
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
$config['remotebuzzer']['usebuttons'] = false;
$config['remotebuzzer']['userotary'] = false;
$config['remotebuzzer']['enable_standalonegallery'] = false;
$config['remotebuzzer']['usenogpio'] = false;
$config['remotebuzzer']['rotaryclkgpio'] = 27;
$config['remotebuzzer']['rotarydtgpio'] = 17;
$config['remotebuzzer']['rotarybtngpio'] = 22;
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
$config['remotebuzzer']['debounce'] = 30;


// S Y N C  T O  U S B  S T I C K
$config['synctodrive']['enabled'] = false;
$config['synctodrive']['target'] = 'photobooth'; //Default target for the sync script
$config['synctodrive']['interval'] = 300;


// G E T  R E Q U E S T
$config['get_request']['countdown'] = false;
$config['get_request']['processed'] = false;
$config['get_request']['server'] = '';
$config['get_request']['picture'] = 'CNTDWNPHOTO';
$config['get_request']['collage'] = 'CNTDWNCOLLAGE';
$config['get_request']['video'] = 'CNTDWNVIDEO';


// A U T H E N T I C A T I O N
$config['login']['enabled'] = false;
$config['login']['username'] = 'Photo';
$config['login']['password'] = '';
$config['protect']['admin'] = true;
$config['protect']['localhost_admin'] = true;
$config['protect']['index'] = false;
$config['protect']['localhost_index'] = false;
$config['protect']['index_redirect'] = 'login';
$config['protect']['manual'] = false;
$config['protect']['localhost_manual'] = false;


// U S E R   I N T E R F A C E
// possible style values: "classic", "modern", "modern_squared", "custom"
$config['ui']['style'] = 'modern_squared';
// possible button values: "rounded", "modern", "modern_squared", "custom"
$config['ui']['button'] = 'modern_squared';
$config['ui']['shutter_animation'] = true;
// possible image values: "none", "top", "bottom", "left", "right", "/private/cheese.png"
$config['ui']['shutter_cheese_img'] = 'none';
$config['button']['show_fs'] = false;
$config['button']['homescreen'] = true;
$config['ui']['result_buttons'] = true;
$config['ui']['font_size'] = '16px';
$config['colors']['countdown'] = '#ffffff';
$config['colors']['background_countdown'] = '#2e2e2e';
$config['colors']['cheese'] = '#ffffff';
$config['background']['defaults'] = '';
$config['background']['admin'] = '';
$config['background']['chroma'] = '';
$config['ui']['decore_lines'] = true;
$config['ui']['rounded_corners'] = false;
$config['colors']['primary'] = '#2e2e2e';
$config['colors']['secondary'] = '#212121';
$config['colors']['highlight'] = '#C0C0C0';
$config['colors']['font'] = '#c9c9c9';
$config['colors']['button_font'] = '#ffffff';
$config['colors']['start_font'] = '#ffffff';
$config['colors']['panel'] = '#212121';
$config['colors']['hover_panel'] = '#2e2e2e';
$config['colors']['border'] = '#eeeeee';
$config['colors']['box'] = '#f8f9fc';
$config['colors']['gallery_button'] = '#ffffff';


// I C O N S
$config['icons']['admin_back'] = 'fa fa-long-arrow-left fa-3x';
$config['icons']['admin_back_short'] = 'fa fa-arrow-left';
$config['icons']['admin_menutoggle'] = 'fa fa-bars fa-3x';
$config['icons']['admin_save'] = 'fa fa-circle-o-notch fa-spin fa-fw';
$config['icons']['admin_save_success'] = 'fa fa-check';
$config['icons']['admin_save_error'] = 'fa fa-times';
$config['icons']['admin_signout'] = 'fa fa-sign-out fa-3x';
$config['icons']['admin'] = 'fa fa-cog';
$config['icons']['home'] = 'fa fa-home';
$config['icons']['gallery'] = 'fa fa-picture-o';
$config['icons']['dependencies'] = 'fa fa-list-ul';
$config['icons']['update'] = 'fa fa-tasks';
$config['icons']['slideshow'] = 'fa fa-play';
$config['icons']['livechroma'] = 'fa fa-paint-brush';
$config['icons']['faq'] = 'fa fa-question-circle';
$config['icons']['manual'] = 'fa fa-info-circle';
$config['icons']['telegram'] = 'fa fa-telegram';
$config['icons']['cups'] = 'fa fa-print';
$config['icons']['take_picture'] = 'fa fa-camera';
$config['icons']['take_collage'] = 'fa fa-th-large';
$config['icons']['take_video'] = 'fa fa-video';
$config['icons']['close'] = 'fa fa-times';
$config['icons']['refresh'] = 'fa fa-refresh';
$config['icons']['delete'] = 'fa fa-trash';
$config['icons']['print'] = 'fa fa-print';
$config['icons']['save'] = 'fa fa-floppy-o';
$config['icons']['download'] = 'fa fa-download';
$config['icons']['qr'] = 'fa fa-qrcode';
$config['icons']['mail'] = 'fa fa-envelope';
$config['icons']['mail_close'] = 'fa fa-times';
$config['icons']['mail_submit'] = 'fa fa-spinner fa-spin';
$config['icons']['filter'] = 'fa fa-magic';
$config['icons']['chroma'] = 'fa fa-paint-brush';
$config['icons']['fullscreen'] = 'fa fa-arrows-alt';
$config['icons']['share'] = 'fa fa-share-alt';
$config['icons']['zoom'] = 'fa fa-search-plus';
$config['icons']['logout'] = 'fa fa-sign-out';
$config['icons']['date'] = 'fa fa-clock-o';
$config['icons']['spinner'] = 'fa fa-cog fa-spin';
$config['icons']['update_git'] = 'fa fa-play-circle';
$config['icons']['password_visibility'] = 'fa fa-eye';
$config['icons']['password_toggle'] = 'fa-eye fa-eye-slash';
$config['icons']['slideshow_play'] = 'fa fa-play';
$config['icons']['slideshow_toggle'] = 'fa-play fa-pause';


// J P E G   Q U A L I T Y
$config['jpeg_quality']['image'] = 100;
$config['jpeg_quality']['chroma'] = 100;
$config['jpeg_quality']['thumb'] = 60;


// C O M M A N D S
$config['take_picture']['cmd'] = '';
$config['take_picture']['msg'] = '';
$config['print']['cmd'] = '';
$config['print']['msg'] = '';
$config['exiftool']['cmd'] = '';
$config['exiftool']['msg'] = '';
$config['preview']['cmd'] = '';
$config['preview']['killcmd'] = '';
$config['nodebin']['cmd'] = '';
$config['pre_photo']['cmd'] = '';
$config['post_photo']['cmd'] = '';
$config['reboot']['cmd'] = '';
$config['shutdown']['cmd'] = '';

// F O L D E R S
$config['folders']['config'] = 'config';
$config['folders']['data'] = 'data';
$config['folders']['images'] = 'images';
$config['folders']['keying'] = 'keying';
$config['folders']['print'] = 'print';
$config['folders']['qrcodes'] = 'qrcodes';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['tmp'] = 'tmp';
$config['folders']['archives'] = 'archives';
$config['folders']['private'] = 'private';

// R E S E T
$config['reset']['remove_images'] = true;
$config['reset']['remove_mailtxt'] = false;
$config['reset']['remove_config'] = false;
