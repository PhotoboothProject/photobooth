<?php

require_once('../lib/config.php');
require_once('../lib/configsetup.inc.php');

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title>Photobooth Manual</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../resources/css/manual.css" />
	<?php if ($config['rounded_corners']): ?>
	<link rel="stylesheet" type="text/css" href="../resources/css/rounded.css" />
	<?php endif; ?>
</head>
<body class="manualwrapper">
	<div class="manual-panel">
		<h2>Photobooth Manual</h2>
		<h3><a class="back-to-pb" href="../">Photobooth</a></h3>

		<div class="accordion">
			<form>
				<?php
					$i = 0;
					foreach($configsetup as $panel => $fields) {
						$open = '';
						if($i == 0){
							$open = ' open init';
						}
						echo '<div class="panel'.$open.'">';
								echo '<div class="panel-heading">';
									echo '<h3><span class="minus">-</span><span class="plus">+</span><span data-i18n="'.$panel.'">'.$panel.'</span></h3>';
								echo '</div>';
								echo '<div class="panel-body">';

								foreach($fields as $key => $field) {
									echo '<div class="form-row">';
									switch($field['type']) {
										case 'checkbox':
											echo '<p><h4><span data-i18n="'.$key.'">'.$key.'</span></h4></p>';
											echo '<p><span data-i18n="manual_'.$key.'">manual_'.$key.'</span></p><hr>';
											echo '</div>';
											break;
										case 'multi-select':
										case 'range':
										case 'select':
										case 'input':
											echo '<p><h4><span data-i18n="'.$panel.'_'.$key.'"></span></h4></p>';
											echo '<p><span data-i18n="manual_'.$panel.'_'.$key.'">manual_'.$panel.'_'.$key.'</span></p><hr>';
											echo '</div>';
											break;
										case 'color':
										case 'hidden':
											echo '<input type="hidden" name="'.$field['name'].'" value="'.$field['value'].'"/>';
											echo '</div>';
											break;
									}
								}
								echo '</div>';
						echo '</div>';
						$i++;
					}
				?>
			</form>
			<a href="https://github.com/andi34/photobooth/wiki" class="btn wiki-btn"><span data-i18n="show_wiki"></span></a>
		</div>
	</div>

	<script type="text/javascript" src="../api/config.php"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/theme.js"></script>
	<script type="module" src="../resources/js/i18n-sub.js"></script>

	<script>
		$(function() {
			$('.panel-heading').on('click', function() {
				const panel = $(this).parents('.panel');
				const others = $(this).parents('.accordion').find('.open').not(panel);

				others.removeClass('open init');

				panel.toggleClass('open');
				panel.find('.panel-body').slideToggle();

				others.find('.panel-body').slideUp('fast');
			});
		});
	</script>

</body>
</html>
