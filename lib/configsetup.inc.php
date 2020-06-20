<?php
require_once(__DIR__ . '/filter.php');

$configsetup = [
	'general' => [
		'language' => [
			'type' => 'select',
			'name' => 'language',
			'placeholder' => $defaultConfig['language'],
			'options' => [
				'de' => 'DE',
				'el' => 'EL',
				'en' => 'EN',
				'es' => 'ES',
				'fr' => 'FR'
			],
			'value' => $config['language']
		],
		'start_screen_title' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['start_screen_title'],
			'name' => 'start_screen_title',
			'value' => $config['start_screen_title']
		],
		'start_screen_subtitle' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['start_screen_subtitle'],
			'name' => 'start_screen_subtitle',
			'value' => $config['start_screen_subtitle']
		],
		'dev' => [
			'type' => 'checkbox',
			'name' => 'dev',
			'value' => $config['dev']
		],
		'pictureRotation' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['pictureRotation'],
			'name' => 'pictureRotation',
			'value' => $config['pictureRotation']
		],
		'keep_images' => [
			'type' => 'checkbox',
			'name' => 'keep_images',
			'value' => $config['keep_images']
		],
		'show_error_messages' => [
			'type' => 'checkbox',
			'name' => 'show_error_messages',
			'value' => $config['show_error_messages']
		],
		'db_file' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['db_file'],
			'name' => 'db_file',
			'value' => $config['db_file']
		],
		'file_format_date' => [
			'type' => 'checkbox',
			'name' => 'file_format_date',
			'value' => $config['file_format_date']
		],
		'use_print' => [
			'type' => 'checkbox',
			'name' => 'use_print',
			'value' => $config['use_print']
		],
		'use_qr' => [
			'type' => 'checkbox',
			'name' => 'use_qr',
			'value' => $config['use_qr']
		],
		'webserver_ip' => [
			'type' => 'input',
			'name' => 'webserver_ip',
			'placeholder' => '127.0.0.1',
			'value' => $config['webserver_ip']
		],
		'wifi_ssid' => [
			'type' => 'input',
			'name' => 'wifi_ssid',
			'placeholder' => 'Photobooth',
			'value' => $config['wifi_ssid']
		],
		'use_download' => [
			'type' => 'checkbox',
			'name' => 'use_download',
			'value' => $config['use_download']
		],
		'use_mail' => [
			'type' => 'checkbox',
			'name' => 'use_mail',
			'value' => $config['use_mail']
		],
		'photo_key' => [
			'type' => 'input',
			'name' => 'photo_key',
			'placeholder' => '',
			'value' => $config['photo_key']
		],
		'collage_key' => [
			'type' => 'input',
			'name' => 'collage_key',
			'placeholder' => '',
			'value' => $config['collage_key']
		],
		'collage_limit' => [
			'type' => 'hidden',
			'name' => 'collage_limit',
			'value' => $config['collage_limit']
		],
		'force_buzzer' => [
			'type' => 'checkbox',
			'name' => 'force_buzzer',
			'value' => $config['force_buzzer']
		],
		'cntdwn_time' => [
			'type' => 'range',
			'name' => 'cntdwn_time',
			'placeholder' => $defaultConfig['cntdwn_time'],
			'value' => $config['cntdwn_time'],
			'range_min' => 1,
			'range_max' => 10,
			'range_step' => 1,
			'unit' => 'seconds'
		],
		'cheese_time' => [
			'type' => 'range',
			'placeholder' => $defaultConfig['cheese_time'],
			'name' => 'cheese_time',
			'value' => $config['cheese_time'],
			'range_min' => 250,
			'range_max' => 10000,
			'range_step' => 250,
			'unit' => 'milliseconds'
		],
		'time_to_live' => [
			'type' => 'range',
			'placeholder' => $defaultConfig['time_to_live'],
			'name' => 'time_to_live',
			'value' => $config['time_to_live'],
			'range_min' => 1000,
			'range_max' => 90000,
			'range_step' => 1000,
			'unit' => 'milliseconds'
		],
		'image_preview_before_processing' => [
			'type' => 'checkbox',
			'name' => 'image_preview_before_processing',
			'value' => $config['image_preview_before_processing']
		],
		'preserve_exif_data' => [
			'type' => 'checkbox',
			'name' => 'preserve_exif_data',
			'value' => $config['preserve_exif_data']
		],
		'use_filter' => [
			'type' => 'checkbox',
			'name' => 'use_filter',
			'value' => $config['use_filter']
		],
		'default_imagefilter' => [
			'type' => 'select',
			'name' => 'default_imagefilter',
			'placeholder' => 'default_imagefilter',
			'options' => AVAILABLE_FILTERS,
			'value' => $config['default_imagefilter']
		],
		'disabled_filters' => [
			'type' => 'multi-select',
			'name' => 'disabled_filters',
			'options' => AVAILABLE_FILTERS,
			'value' => $config['disabled_filters']
		],
		'polaroid_effect' => [
			'type' => 'checkbox',
			'name' => 'polaroid_effect',
			'value' => $config['polaroid_effect']
		],
		'polaroid_rotation' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['polaroid_rotation'],
			'name' => 'polaroid_rotation',
			'value' => $config['polaroid_rotation']
		],
		'take_frame' => [
			'type' => 'checkbox',
			'name' => 'take_frame',
			'value' => $config['take_frame']
		],
		'take_frame_path' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['take_frame_path'],
			'name' => 'take_frame_path',
			'value' => $config['take_frame_path']
		],
		'chroma_keying' => [
			'type' => 'checkbox',
			'name' => 'chroma_keying',
			'value' => $config['chroma_keying']
		],
		'use_collage' => [
			'type' => 'checkbox',
			'name' => 'use_collage',
			'value' => $config['use_collage']
		],
		'take_collage_frame' => [
			'type' => 'checkbox',
			'name' => 'take_collage_frame',
			'value' => $config['take_collage_frame']
		],
		'take_collage_frame_path' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['take_collage_frame_path'],
			'name' => 'take_collage_frame_path',
			'value' => $config['take_collage_frame_path']
		],
		'collage_cntdwn_time' => [
			'type' => 'range',
			'name' => 'collage_cntdwn_time',
			'placeholder' => $defaultConfig['collage_cntdwn_time'],
			'value' => $config['collage_cntdwn_time'],
			'range_min' => 1,
			'range_max' => 10,
			'range_step' => 1,
			'unit' => 'seconds'
		],
		'continuous_collage' => [
			'type' => 'checkbox',
			'name' => 'continuous_collage',
			'value' => $config['continuous_collage']
		],
		'previewFromCam' => [
			'type' => 'checkbox',
			'name' => 'previewFromCam',
			'value' => $config['previewFromCam']
		],
		'previewCamTakesPic' => [
			'type' => 'checkbox',
			'name' => 'previewCamTakesPic',
			'value' => $config['previewCamTakesPic']
		],
		'previewCamFlipHorizontal' => [
			'type' => 'checkbox',
			'name' => 'previewCamFlipHorizontal',
			'value' => $config['previewCamFlipHorizontal']
		],
		'previewCamBackground' => [
			'type' => 'checkbox',
			'name' => 'previewCamBackground',
			'value' => $config['previewCamBackground']
		],
		'previewFromIPCam' => [
			'type' => 'checkbox',
			'name' => 'previewFromIPCam',
			'value' => $config['previewFromIPCam']
		],
		'ipCamPreviewRotation' => [
			'type' => 'select',
			'name' => 'ipCamPreviewRotation',
			'placeholder' => $defaultConfig['ipCamPreviewRotation'],
			'options' => [
				'0deg' => 'No rotation',
				'90deg' => '90°',
				'-90deg' => '-90°',
				'180deg' => '180°',
				'45deg' => '45°',
				'-45deg' => '-45°'
			],
			'value' => $config['ipCamPreviewRotation']
		],
		'ipCamURL' => [
			'type' => 'input',
			'name' => 'ipCamURL',
			'placeholder' => 'url(http://localhost:8081)',
			'value' => $config['ipCamURL']
		],
		'videoWidth' => [
			'type' => 'input',
			'name' => 'videoWidth',
			'placeholder' => $defaultConfig['videoWidth'],
			'value' => $config['videoWidth']
		],
		'videoHeight' => [
			'type' => 'input',
			'name' => 'videoHeight',
			'placeholder' => $defaultConfig['videoHeight'],
			'value' => $config['videoHeight']
		],
		'camera_mode' => [
			'type' => 'select',
			'name' => 'camera_mode',
			'placeholder' => $defaultConfig['camera_mode'],
			'options' => [
				'user' => 'Front facing camera',
				'environment' => 'Back facing camera'
			],
			'value' => $config['camera_mode']
		],
		'allow_delete' => [
			'type' => 'checkbox',
			'name' => 'allow_delete',
			'value' => $config['allow_delete']
		]
	],
	'jpeg_quality' => [
		'jpeg_quality_image' => [
			'type' => 'range',
			'name' => 'jpeg_quality_image',
			'placeholder' => $defaultConfig['jpeg_quality_image'],
			'value' => $config['jpeg_quality_image'],
			'range_min' => -1,
			'range_max' => 100,
			'range_step' => 1,
			'unit' => 'percent'
		],
		'jpeg_quality_chroma' => [
			'type' => 'range',
			'name' => 'jpeg_quality_chroma',
			'placeholder' => $defaultConfig['jpeg_quality_chroma'],
			'value' => $config['jpeg_quality_chroma'],
			'range_min' => -1,
			'range_max' => 100,
			'range_step' => 1,
			'unit' => 'percent'
		],
		'jpeg_quality_thumb' => [
			'type' => 'range',
			'name' => 'jpeg_quality_thumb',
			'placeholder' => $defaultConfig['jpeg_quality_thumb'],
			'value' => $config['jpeg_quality_thumb'],
			'range_min' => -1,
			'range_max' => 100,
			'range_step' => 1,
			'unit' => 'percent'
		]
	],
	'user_interface' => [
		'font_size' => [
			'type' => 'input',
			'name' => 'font_size',
			'placeholder' => $defaultConfig['font_size'],
			'value' => $config['font_size']
		],
		'background_image' => [
			'type' => 'input',
			'name' => 'background_image',
			'placeholder' => 'url(../img/bg.jpg)',
			'value' => $config['background_image']
		],
		'background_admin' => [
			'type' => 'input',
			'name' => 'background_admin',
			'placeholder' => 'url(../img/bg.jpg)',
			'value' => $config['background_admin']
		],
		'background_chroma' => [
			'type' => 'input',
			'name' => 'background_chroma',
			'placeholder' => 'url(../img/bg.jpg)',
			'value' => $config['background_chroma']
		],
		'show_fork' => [
			'type' => 'checkbox',
			'name' => 'show_fork',
			'value' => $config['show_fork']
		],
		'cups_button' => [
			'type' => 'checkbox',
			'name' => 'cups_button',
			'value' => $config['cups_button']
		],
		'toggle_fs_button' => [
			'type' => 'checkbox',
			'name' => 'toggle_fs_button',
			'value' => $config['toggle_fs_button']
		],
		'rounded_corners' => [
			'type' => 'checkbox',
			'name' => 'rounded_corners',
			'value' => $config['rounded_corners']
		],
		'colors_primary' => [
			'type' => 'color',
			'name' => 'colors[primary]',
			'placeholder' => $defaultConfig['colors']['primary'],
			'value' => $config['colors']['primary']
		],
		'colors_secondary' => [
			'type' => 'color',
			'name' => 'colors[secondary]',
			'placeholder' => $defaultConfig['colors']['secondary'],
			'value' => $config['colors']['secondary']
		],
		'colors_font' => [
			'type' => 'color',
			'name' => 'colors[font]',
			'placeholder' => $defaultConfig['colors']['font'],
			'value' => $config['colors']['font']
		],
		'colors_button_font' => [
			'type' => 'color',
			'name' => 'colors[button_font]',
			'placeholder' => $defaultConfig['colors']['button_font'],
			'value' => $config['colors']['button_font']
		],
		'colors_start_font' => [
			'type' => 'color',
			'name' => 'colors[start_font]',
			'placeholder' => $defaultConfig['colors']['start_font'],
			'value' => $config['colors']['start_font']
		],
		'colors_panel' => [
			'type' => 'color',
			'name' => 'colors[panel]',
			'placeholder' => $defaultConfig['colors']['panel'],
			'value' => $config['colors']['panel']
		],
		'colors_hover_panel' => [
			'type' => 'color',
			'name' => 'colors[hover_panel]',
			'placeholder' => $defaultConfig['colors']['hover_panel'],
			'value' => $config['colors']['hover_panel']
		],
		'colors_border' => [
			'type' => 'color',
			'name' => 'colors[border]',
			'placeholder' => $defaultConfig['colors']['border'],
			'value' => $config['colors']['border']
		],
		'colors_box' => [
			'type' => 'color',
			'name' => 'colors[box]',
			'placeholder' => $defaultConfig['colors']['box'],
			'value' => $config['colors']['box']
		],
		'colors_gallery_button' => [
			'type' => 'color',
			'name' => 'colors[gallery_button]',
			'placeholder' => $defaultConfig['colors']['gallery_button'],
			'value' => $config['colors']['gallery_button']
		],
		'colors_countdown' => [
			'type' => 'color',
			'name' => 'colors[countdown]',
			'placeholder' => $defaultConfig['colors']['countdown'],
			'value' => $config['colors']['countdown']
		],
		'colors_background_countdown' => [
			'type' => 'color',
			'name' => 'colors[background_countdown]',
			'placeholder' => $defaultConfig['colors']['background_countdown'],
			'value' => $config['colors']['background_countdown']
		],
		'colors_cheese' => [
			'type' => 'color',
			'name' => 'colors[cheese]',
			'placeholder' => $defaultConfig['colors']['cheese'],
			'value' => $config['colors']['cheese']
		]
	],
	'login' => [
		'login_enabled' => [
			'type' => 'checkbox',
			'name' => 'login_enabled',
			'value' => $config['login_enabled']
		],
		'username' => [
			'type' => 'input',
			'placeholder' => 'Photo',
			'name' => 'login_username',
			'value' => $config['login_username']
		],
		'password' => [
			'type' => 'input',
			'placeholder' => NULL,
			'name' => 'login_password',
			'value' => $config['login_password']
		],
		'protect_admin' => [
			'type' => 'checkbox',
			'name' => 'protect_admin',
			'value' => $config['protect_admin']
		],
		'protect_index' => [
			'type' => 'checkbox',
			'name' => 'protect_index',
			'value' => $config['protect_index']
		]
	],
	'folders' => [
		'images' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['images'],
			'name' => 'folders[images]',
			'value' => $config['folders']['images']
		],
		'keying' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['keying'],
			'name' => 'folders[keying]',
			'value' => $config['folders']['keying']
		],
		'print' => [
			'type' => 'input',
			'name' => 'folders[print]',
			'placeholder' => $defaultConfig['folders']['print'],
			'value' => $config['folders']['print']
		],
		'qrcodes' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['qrcodes'],
			'name' => 'folders[qrcodes]',
			'value' => $config['folders']['qrcodes']
		],
		'thumbs' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['thumbs'],
			'name' => 'folders[thumbs]',
			'value' => $config['folders']['thumbs']
		],
		'tmp' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['tmp'],
			'name' => 'folders[tmp]',
			'value' => $config['folders']['tmp']
		],
		'data' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['folders']['data'],
			'name' => 'folders[data]',
			'value' => $config['folders']['data']
		]
	],
	'event' => [
		'is_event' => [
			'type' => 'checkbox',
			'name' => 'is_event',
			'value' => $config['is_event']
		],
		'textLeft' => [
			'type' => 'input',
			'placeholder' => 'Name 1',
			'name' => 'event[textLeft]',
			'value' => $config['event']['textLeft']
		],
		'textRight' => [
			'type' => 'input',
			'placeholder' => 'Name 2',
			'name' => 'event[textRight]',
			'value' => $config['event']['textRight']
		],
		'symbol' => [
			'type' => 'select',
			'name' => 'event[symbol]',
			'placeholder' => $defaultConfig['event']['symbol'],
			'options' => [
				'fa-camera' => 'Camera',
				'fa-camera-retro' => 'Camera Retro',
				'fa-birthday-cake' => 'Birthday Cake',
				'fa-gift' => 'Gift',
				'fa-tree' => 'Tree',
				'fa-snowflake-o' => 'Snowflake',
				'fa-heart-o' => 'Heart',
				'fa-heart' => 'Heart filled',
				'fa-heartbeat' => 'Heartbeat',
				'fa-apple' => 'Apple',
				'fa-anchor' => 'Anchor',
				'fa-glass' => 'Glass',
				'fa-gears' => 'Gears',
				'fa-users' => 'People'
			],
			'value' => $config['event']['symbol']
		]
	],
	'print' => [
		'print_qrcode' => [
			'type' => 'checkbox',
			'name' => 'print_qrcode',
			'value' => $config['print_qrcode']
		],
		'print_frame' => [
			'type' => 'checkbox',
			'name' => 'print_frame',
			'value' => $config['print_frame']
		],
		'frame_path' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['print_frame_path'],
			'name' => 'print_frame_path',
			'value' => $config['print_frame_path']
		],
		'crop_onprint' => [
			'type' => 'checkbox',
			'name' => 'crop_onprint',
			'value' => $config['crop_onprint']
		],
		'crop_width' => [
			'type' => 'input',
			'name' => 'crop_width',
			'placeholder' => $defaultConfig['crop_width'],
			'value' => $config['crop_width']
		],
		'crop_height' => [
			'type' => 'input',
			'name' => 'crop_height',
			'placeholder' => $defaultConfig['crop_height'],
			'value' => $config['crop_height']
		],
		'is_textonprint' => [
			'type' => 'checkbox',
			'name' => 'is_textonprint',
			'value' => $config['is_textonprint']
		],
		'line1' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['textonprint']['line1'],
			'name' => 'textonprint[line1]',
			'value' => $config['textonprint']['line1']
		],
		'line2' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['textonprint']['line2'],
			'name' => 'textonprint[line2]',
			'value' => $config['textonprint']['line2']
		],
		'line3' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['textonprint']['line3'],
			'name' => 'textonprint[line3]',
			'value' => $config['textonprint']['line3']
		],
		'locationx' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['locationx'],
			'name' => 'locationx',
			'value' => $config['locationx']
		],
		'locationy' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['locationy'],
			'name' => 'locationy',
			'value' => $config['locationy']
		],
		'rotation' => [
			'type' => 'range',
			'placeholder' => $defaultConfig['rotation'],
			'name' => 'rotation',
			'value' => $config['rotation'],
			'range_min' => -180,
			'range_max' => 180,
			'range_step' => 5,
			'unit' => 'degrees'
		],
		'font_path' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['font_path'],
			'name' => 'font_path',
			'value' => $config['font_path']
		],
		'fontsize' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['fontsize'],
			'name' => 'fontsize',
			'value' => $config['fontsize']
		],
		'linespace' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['linespace'],
			'name' => 'linespace',
			'value' => $config['linespace']
		],
	],
	'gallery' => [
		'show_gallery' => [
			'type' => 'checkbox',
			'name' => 'show_gallery',
			'value' => $config['show_gallery']
		],
		'newest_first' => [
			'type' => 'checkbox',
			'name' => 'newest_first',
			'value' => $config['newest_first']
		],
		'scrollbar' => [
			'type' => 'checkbox',
			'name' => 'scrollbar',
			'value' => $config['scrollbar']
		],
		'show_date' => [
			'type' => 'checkbox',
			'name' => 'show_date',
			'value' => $config['show_date']
		],
		'date_format' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['gallery']['date_format'],
			'name' => 'gallery[date_format]',
			'value' => $config['gallery']['date_format']
		],
		'gallery_bottom_bar' => [
			'type' => 'checkbox',
			'name' => 'gallery_bottom_bar',
			'value' => $config['gallery_bottom_bar']
		]
	],
	'mail' => [
		'send_all_later' => [
			'type' => 'checkbox',
			'name' => 'send_all_later',
			'value' => $config['send_all_later']
		],
		'file' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_file'],
			'name' => 'mail_file',
			'value' => $config['mail_file']
		],
		'host' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_host'],
			'name' => 'mail_host',
			'value' => $config['mail_host']
		],
		'username' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_username'],
			'name' => 'mail_username',
			'value' => $config['mail_username']
		],
		'password' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_password'],
			'name' => 'mail_password',
			'value' => $config['mail_password']
		],
		'secure' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_secure'],
			'name' => 'mail_secure',
			'value' => $config['mail_secure']
		],
		'port' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_port'],
			'name' => 'mail_port',
			'value' => $config['mail_port']
		],
		'fromAddress' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_fromAddress'],
			'name' => 'mail_fromAddress',
			'value' => $config['mail_fromAddress']
		],
		'fromName' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_fromName'],
			'name' => 'mail_fromName',
			'value' => $config['mail_fromName']
		],
		'subject' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_subject'],
			'name' => 'mail_subject',
			'value' => $config['mail_subject']
		],
		'text' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['mail_text'],
			'name' => 'mail_text',
			'value' => $config['mail_text']
		],
	],
	'slideshow' => [
		'refreshTime' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['slideshow_refreshTime'],
			'name' => 'slideshow_refreshTime',
			'value' => $config['slideshow_refreshTime']
		],
		'pictureTime' => [
			'type' => 'input',
			'placeholder' => $defaultConfig['slideshow_pictureTime'],
			'name' => 'slideshow_pictureTime',
			'value' => $config['slideshow_pictureTime']
		],
		'randomPicture' => [
			'type' => 'checkbox',
			'name' => 'slideshow_randomPicture',
			'value' => $config['slideshow_randomPicture']
		],
		'use_thumbs' => [
			'type' => 'checkbox',
			'name' => 'slideshow_use_thumbs',
			'value' => $config['slideshow_use_thumbs']
		]
	],
	'commands' => [
		'take_picture_cmd' => [
			'type' => 'input',
			'placeholder' => 'take_picture_cmd',
			'name' => 'take_picture[cmd]',
			'value' => $config['take_picture']['cmd']
		],
		'take_picture_msg' => [
			'type' => 'input',
			'placeholder' => 'take_picture_msg',
			'name' => 'take_picture[msg]',
			'value' => $config['take_picture']['msg']
		],
		'print_cmd' => [
			'type' => 'input',
			'placeholder' => 'print_cmd',
			'name' => 'print[cmd]',
			'value' => $config['print']['cmd']
		],
		'print_msg' => [
			'type' => 'input',
			'placeholder' => 'print_msg',
			'name' => 'print[msg]',
			'value' => $config['print']['msg']
		],
		'exiftool_cmd' => [
			'type' => 'input',
			'placeholder' => 'exiftool_cmd',
			'name' => 'exiftool[cmd]',
			'value' => $config['exiftool']['cmd']
		],
		'exiftool_msg' => [
			'type' => 'input',
			'placeholder' => 'exiftool_msg',
			'name' => 'exiftool[msg]',
			'value' => $config['exiftool']['msg']
		]
	],
	'reset' => [
		'remove_images' => [
			'type' => 'checkbox',
			'name' => 'reset_remove_images',
			'value' => $config['reset_remove_images']
		],
		'remove_mailtxt' => [
			'type' => 'checkbox',
			'name' => 'reset_remove_mailtxt',
			'value' => $config['reset_remove_mailtxt']
		],
		'remove_config' => [
			'type' => 'checkbox',
			'name' => 'reset_remove_config',
			'value' => $config['reset_remove_config']
		]
	]
];
