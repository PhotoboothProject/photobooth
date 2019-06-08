<?php

require_once('../config.inc.php');

	$configsetup = [
		'general' => [
			'language' => [
				'type' => 'select',
				'name' => 'language',
				'placeholder' => 'language',
				'options' => [
					'de' => 'DE',
					'fr' => 'FR',
					'en' => 'EN'
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
			'use_qr' => [
				'type' => 'checkbox',
				'name' => 'use_qr',
				'value' => $config['use_qr']
			],
			'use_mail' => [
				'type' => 'checkbox',
				'name' => 'use_mail',
				'value' => $config['use_mail']
			],
			'show_fork' => [
				'type' => 'checkbox',
				'name' => 'show_fork',
				'value' => $config['show_fork']
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
			'previewFromCam' => [
				'type' => 'checkbox',
				'name' => 'previewFromCam',
				'value' => $config['previewFromCam']
			]
		],
		'folders' => [
			'images' => [
				'type' => 'input',
				'placeholder' => 'images',
				'name' => 'folders[images]',
				'value' => $config['folders']['images']
			],
			'thumbs' => [
				'type' => 'input',
				'placeholder' => 'thumbs',
				'name' => 'folders[thumbs]',
				'value' => $config['folders']['thumbs']
			],
			'qrcodes' => [
				'type' => 'input',
				'placeholder' => 'qrcodes',
				'name' => 'folders[qrcodes]',
				'value' => $config['folders']['qrcodes']
			],
			'print' => [
				'type' => 'input',
				'placeholder' => 'print',
				'name' => 'folders[print]',
				'value' => $config['folders']['print']
			]
		],
		'gallery' => [
			'show_gallery' => [
				'type' => 'checkbox',
				'name' => 'show_gallery',
				'value' => $config['gallery']['show_gallery']
			],
			'newest_first' => [
				'type' => 'checkbox',
				'name' => 'newest_first',
				'value' => $config['gallery']['newest_first']
			],
			'scrollbar' => [
				'type' => 'checkbox',
				'name' => 'scrollbar',
				'value' => $config['gallery']['scrollbar']
			],
			'show_date' => [
				'type' => 'checkbox',
				'name' => 'show_date',
				'value' => $config['gallery']['show_date']
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
				'value' => $config['take_picture']['cmd']
			],
			'print_msg' => [
				'type' => 'input',
				'placeholder' => 'print_msg',
				'name' => 'print[msg]',
				'value' => $config['take_picture']['msg']
			]
		]
	];
?>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<title>Photobooth</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="/resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/resources/img/favicon-16x16.png">
	<link rel="manifest" href="/resources/img/site.webmanifest">
	<link rel="mask-icon" href="/resources/img/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="theme-color" content="#ffffff">

	<link rel="stylesheet" type="text/css" href="/resources/css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="/resources/css/font-awesome.min.css" />
	<link rel="stylesheet" type="text/css" href="/resources/css/default-skin/default-skin.css">
	<link rel="stylesheet" type="text/css" href="/resources/css/style.css" />
	<link rel="stylesheet" href="/resources/css/admin.css" />
	<script type="text/javascript">
		var isdev = true;
		var gallery_newest_first = <?php echo ($config['gallery']['newest_first']) ? 'true' : 'false'; ?>;
	</script>
</head>
<body class="deselect">
<div id="admin-settings">
	<div class="admin-panel">
		<h2><a class="back-to-pb" href="/">Photobooth</a></h2>
		<button class="reset-btn">
			<span class="save">
				<span data-l10n="reset"></span>
			</span>
			<span class="saving">
				<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>
				<span data-l10n="saving"></span>
			</span>
			<span class="success">
				<i class="fa fa-check"></i>
				<span data-l10n="success"></span>
			</span>
			<span class="error">
				<i class="fa fa-times"></i>
				<span data-l10n="saveerror"></span>
			</span>
		</button>
		<div class="accordion">
			<form>
				<?php
					$i = 0;
					foreach($configsetup as $panel => $fields) {
						$open = '';
						if($i == 0){
							$open = ' open init';
						}
						echo '<div class="panel'.$open.'"><div class="panel-heading"><h3><span class="minus">-</span><span class="plus">+</span><span data-l10n="'.$panel.'">'.$panel.'</span></h3></div>
									<div class="panel-body">
						';

						foreach($fields as $key => $field){
							echo '<div class="form-row">';
							switch($field['type']) {
								case 'input':
									echo '<label data-l10n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label><input type="text" name="'.$field['name'].'" value="'.$field[
										'value'].'" placeholder="'.$field['placeholder'].'"/>';
								break;
								case 'checkbox':
									$checked = '';
									if ($field['value'] == 'true') {
										$checked = ' checked="checked"';
									}
									echo '<label><input type="checkbox" '.$checked.' name="'.$field['name'].'" value="true"/><span data-l10n="'.$key.'">'.$key.'</span></label>';
								break;
								case 'select':
									echo '<label data-l10n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label><select name="'.$field['name'].'">
										<option data-l10n="'.$key.'"></option>
									';
										foreach($field['options'] as $val => $option) {
											$selected = '';
											if ($val == $field['value']) {
												$selected = ' selected="selected"';
											}
											echo '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
										}
									echo '</select>';
								break;
							}
							echo '</div>';
						}
						echo '</div></div>';
						$i++;
					}
				?>
			</form>
			<button class="save-btn">
				<span class="save">
					<span data-l10n="save"></span>
				</span>
				<span class="saving">
					<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>
					<span data-l10n="saving"></span>
				</span>
				<span class="success">
					<i class="fa fa-check"></i>
					<span data-l10n="success"></span>
				</span>
				<span class="error">
					<i class="fa fa-times"></i>
					<span data-l10n="saveerror"></span>
				</span>
			</button>
		</div>
	</div>

</div>
<script type="text/javascript" src="/resources/js/jquery.js"></script>
<script type="text/javascript" src="/resources/js/l10n.js"></script>
<script type="text/javascript" src="/resources/js/admin.js"></script>
<script type="text/javascript" src="/lang/<?php echo $config['language']; ?>.js"></script>

</body>
</html>
