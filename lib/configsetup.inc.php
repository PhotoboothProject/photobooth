<?php
$configsetup = [
	'general' => [
		'language' => [
			'type' => 'select',
			'name' => 'language',
			'placeholder' => 'language',
			'options' => [
				'de' => 'DE',
				'en' => 'EN',
				'es' => 'ES',
				'fr' => 'FR',
				'gr' => 'GR'
			],
			'value' => $config['language']
		],
		'start_screen_title' => [
			'type' => 'input',
			'placeholder' => 'Photobooth',
			'name' => 'start_screen_title',
			'value' => $config['start_screen_title']
		],
		'start_screen_subtitle' => [
			'type' => 'input',
			'placeholder' => 'Webinterface by André Rinas',
			'name' => 'start_screen_subtitle',
			'value' => $config['start_screen_subtitle']
		],
		'dev' => [
			'type' => 'checkbox',
			'name' => 'dev',
			'value' => $config['dev']
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
		'crop_onprint' => [
			'type' => 'checkbox',
			'name' => 'crop_onprint',
			'value' => $config['crop_onprint']
		],
		'crop_width' => [
			'type' => 'input',
			'name' => 'crop_width',
			'placeholder' => '1000',
			'value' => $config['crop_width']
		],
		'crop_height' => [
			'type' => 'input',
			'name' => 'crop_height',
			'placeholder' => '500',
			'value' => $config['crop_height']
		],
		'use_qr' => [
			'type' => 'checkbox',
			'name' => 'use_qr',
			'value' => $config['use_qr']
		],
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
		'force_buzzer' => [
			'type' => 'checkbox',
			'name' => 'force_buzzer',
			'value' => $config['force_buzzer']
		],
		'cntdwn_time' => [
			'type' => 'input',
			'name' => 'cntdwn_time',
			'placeholder' => '5',
			'value' => $config['cntdwn_time']
		],
		'cheese_time' => [
			'type' => 'input',
			'placeholder' => '1000',
			'name' => 'cheese_time',
			'value' => $config['cheese_time']
		],
		'use_filter' => [
			'type' => 'checkbox',
			'name' => 'use_filter',
			'value' => $config['use_filter']
		],
		'polaroid_effect' => [
			'type' => 'checkbox',
			'name' => 'polaroid_effect',
			'value' => $config['polaroid_effect']
		],
		'polaroid_rotation' => [
			'type' => 'input',
			'placeholder' => '0',
			'name' => 'polaroid_rotation',
			'value' => $config['polaroid_rotation']
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
		'collage_cntdwn_time' => [
			'type' => 'input',
			'name' => 'collage_cntdwn_time',
			'placeholder' => '3',
			'value' => $config['collage_cntdwn_time']
		],
		'continuous_collage' => [
			'type' => 'checkbox',
			'name' => 'continuous_collage',
			'value' => $config['continuous_collage']
		],
		'cups_button' => [
			'type' => 'checkbox',
			'name' => 'cups_button',
			'value' => $config['cups_button']
		],
		'previewFromCam' => [
			'type' => 'checkbox',
			'name' => 'previewFromCam',
			'value' => $config['previewFromCam']
		]
	],
	'user_interface' => [
		'show_fork' => [
			'type' => 'checkbox',
			'name' => 'show_fork',
			'value' => $config['show_fork']
		],
		'color_theme' => [
			'type' => 'select',
			'name' => 'color_theme',
			'placeholder' => 'default',
			'options' => [
				'default' => 'default',
				'blue-gray' => 'blue-gray'
			],
			'value' => $config['color_theme']
		]
	],
	'folders' => [
		'images' => [
			'type' => 'input',
			'placeholder' => 'images',
			'name' => 'folders[images]',
			'value' => $config['folders']['images']
		],
		'keying' => [
			'type' => 'input',
			'placeholder' => 'keying',
			'name' => 'folders[keying]',
			'value' => $config['folders']['keying']
		],
		'print' => [
			'type' => 'input',
			'placeholder' => 'print',
			'name' => 'folders[print]',
			'value' => $config['folders']['print']
		],
		'qrcodes' => [
			'type' => 'input',
			'placeholder' => 'qrcodes',
			'name' => 'folders[qrcodes]',
			'value' => $config['folders']['qrcodes']
		],
		'thumbs' => [
			'type' => 'input',
			'placeholder' => 'thumbs',
			'name' => 'folders[thumbs]',
			'value' => $config['folders']['thumbs']
		],
		'tmp' => [
			'type' => 'input',
			'placeholder' => 'tmp',
			'name' => 'folders[tmp]',
			'value' => $config['folders']['tmp']
		],
		'data' => [
			'type' => 'input',
			'placeholder' => 'data',
			'name' => 'folders[data]',
			'value' => $config['folders']['data']
		]
	],
	'wedding' => [
		'is_wedding' => [
			'type' => 'checkbox',
			'name' => 'is_wedding',
			'value' => $config['is_wedding']
		],
		'groom' => [
			'type' => 'input',
			'placeholder' => 'Name 1',
			'name' => 'wedding[groom]',
			'value' => $config['wedding']['groom']
		],
		'bride' => [
			'type' => 'input',
			'placeholder' => 'Name 2',
			'name' => 'wedding[bride]',
			'value' => $config['wedding']['bride']
		],
		'symbol' => [
			'type' => 'select',
			'name' => 'wedding[symbol]',
			'placeholder' => 'wedding[symbol]',
			'options' => [
				'fa-heart-o' => 'Heart',
				'fa-heart' => 'Heart filled',
				'fa-heartbeat' => 'Heartbeat',
				'fa-anchor' => 'Anchor',
				'fa-glass' => 'Glass'
			],
			'value' => $config['wedding']['symbol']
		]
	],
	// text print start
	'textonprint' => [
		'is_textonprint' => [
			'type' => 'checkbox',
			'name' => 'is_textonprint',
			'value' => $config['is_textonprint']
		],
		'line1' => [
			'type' => 'input',
			'placeholder' => 'line 1',
			'name' => 'textonprint[line1]',
			'value' => $config['textonprint']['line1']
		],
		'line2' => [
			'type' => 'input',
			'placeholder' => 'line 2',
			'name' => 'textonprint[line2]',
			'value' => $config['textonprint']['line2']
		],
		'line3' => [
			'type' => 'input',
			'placeholder' => 'line 3',
			'name' => 'textonprint[line3]',
			'value' => $config['textonprint']['line3']
		],
		'locationx' => [
			'type' => 'input',
			'placeholder' => '2250',
			'name' => 'locationx',
			'value' => $config['locationx']
		],
		'locationy' => [
			'type' => 'input',
			'placeholder' => '1050',
			'name' => 'locationy',
			'value' => $config['locationy']
		],
		'rotation' => [
			'type' => 'input',
			'placeholder' => '40',
			'name' => 'rotation',
			'value' => $config['rotation']
		],
		'fontsize' => [
			'type' => 'input',
			'placeholder' => '100',
			'name' => 'fontsize',
			'value' => $config['fontsize']
		],
		'linespace' => [
			'type' => 'input',
			'placeholder' => '100',
			'name' => 'linespace',
			'value' => $config['linespace']
		],
	],
	// text print end
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
			'placeholder' => 'd.m.Y - G:i',
			'name' => 'gallery[date_format]',
			'value' => $config['gallery']['date_format']
		]
	],
	'mail' => [
		'send_all_later' => [
			'type' => 'checkbox',
			'name' => 'send_all_later',
			'value' => $config['send_all_later']
		],
		'host' => [
			'type' => 'input',
			'placeholder' => 'smtp.example.com',
			'name' => 'mail_host',
			'value' => $config['mail_host']
		],
		'username' => [
			'type' => 'input',
			'placeholder' => 'photobooth@example.com',
			'name' => 'mail_username',
			'value' => $config['mail_username']
		],
		'password' => [
			'type' => 'input',
			'placeholder' => 'yourpassword',
			'name' => 'mail_password',
			'value' => $config['mail_password']
		],
		'secure' => [
			'type' => 'input',
			'placeholder' => 'tls',
			'name' => 'mail_secure',
			'value' => $config['mail_secure']
		],
		'port' => [
			'type' => 'input',
			'placeholder' => '587',
			'name' => 'mail_port',
			'value' => $config['mail_port']
		],
		'fromAddress' => [
			'type' => 'input',
			'placeholder' => 'photobooth@example.com',
			'name' => 'mail_fromAddress',
			'value' => $config['mail_fromAddress']
		],
		'fromName' => [
			'type' => 'input',
			'placeholder' => 'Photobooth',
			'name' => 'mail_fromName',
			'value' => $config['mail_fromName']
		],
		'subject' => [
			'type' => 'input',
			'placeholder' => 'Here is your picture',
			'name' => 'mail_subject',
			'value' => $config['mail_subject']
		],
		'text' => [
			'type' => 'input',
			'placeholder' => 'Hey, your picture is attached.',
			'name' => 'mail_text',
			'value' => $config['mail_text']
		],
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
		]
	]
];