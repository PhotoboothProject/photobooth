<?php
session_start();

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

	<title>Photobooth</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../resources/css/admin.css" />
	<?php if ($config['rounded_corners']): ?>
	<link rel="stylesheet" type="text/css" href="../resources/css/rounded.css" />
	<?php endif; ?>
</head>
<body class="adminwrapper">
	<div class="admin-panel">
		<h2><a class="back-to-pb" href="../">Photobooth</a></h2>
		<?php if( !$config['login_enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect_admin']): ?>
		<button class="reset-btn">
			<span class="save">
				<span data-i18n="reset"></span>
			</span>
			<span class="saving">
				<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>
				<span data-i18n="saving"></span>
			</span>
			<span class="success">
				<i class="fa fa-check"></i>
				<span data-i18n="success"></span>
			</span>
			<span class="error">
				<i class="fa fa-times"></i>
				<span data-i18n="saveerror"></span>
			</span>
		</button>

		<?php if(isset($_SESSION['auth']) && $_SESSION['auth'] === true): ?>
		<p><a href="../login/logout.php" class="btn btn--tiny btn--flex fa fa-sign-out"><span data-i18n="logout"></span></a></p>
		<?php endif; ?>

		<div id="diskUsage">
			<a href="diskusage.php" class="btn btn--tiny btn--flex"><span data-i18n="disk_usage"></span></a>
		</div>

		<div class="accordion">
			<form>
				<?php
					$i = 0;
					foreach($configsetup as $panel => $fields) {
						if (! empty($fields['platform']) && $fields['platform'] != 'all' && $fields['platform'] != $os) {
							continue;
						};

						$open = '';
						if($i == 0){
							$open = ' open init';
						}
						echo '<div class="panel'.$open.'"><div class="panel-heading"><h3><span class="minus">-</span><span class="plus">+</span><span data-i18n="'.$panel.'">'.$panel.'</span> <a href="../manual" title="Need help?" target="newwin"><i class="fa fa-info-circle" aria-hidden="true"></i></a></h3></div>
									<div class="panel-body">
						';

						foreach($fields as $key => $field){
							if ($key == 'platform') {
								continue;
							};
							echo '<div class="form-row">';
							switch($field['type']) {
								case 'input':
									echo '<label data-i18n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label><input type="text" name="'.$field['name'].'" value="'.$field['value'].'" placeholder="'.$field['placeholder'].'"/>';
									break;
								case 'range':
									echo '<label data-i18n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label></br>
										<div class="'.$field['name'].'"><span>'.$field['value'].'</span> <span data-i18n="'.$field['unit'].'"</span></div>
										<input type="range" name="'.$field['name'].'" class="slider" value="'.$field['value'].'" min="'.$field['range_min'].'" max="'.$field['range_max'].'" step="'.$field['range_step'].'" placeholder="'.$field['placeholder'].'"/>
										<script>
										window.addEventListener("load", function() {
											var slider = document.querySelector("input[name='.$field['name'].']");
											slider.addEventListener("change", function() {
												document.querySelector(".'.$field['name'].' span").innerHTML = this.value;
											});
										});
										</script>';
									break;
								case 'color':
									echo '<input type="color" name="'.$field['name'].'" value="'.$field['value'].'" placeholder="'.$field['placeholder'].'"/>
										<label data-i18n="'.$panel.'_'.$key.'"> '.$panel.'_'.$key.'</label>';
									break;
								case 'hidden':
									echo '<input type="hidden" name="'.$field['name'].'" value="'.$field['value'].'"/>';
									break;
								case 'checkbox':
									$checked = '';
									if ($field['value'] == 'true') {
										$checked = ' checked="checked"';
									}
									echo '<span data-i18n="'.$key.'">'.$key.'</span></br>
										<label class="switch"><input type="checkbox" '.$checked.' name="'.$field['name'].'" value="true"/><span class="toggle"></span></label>';
									break;
								case 'multi-select':
								case 'select':
									$selectTag = '<select name="'.$field['name'] . ($field['type'] === 'multi-select' ? '[]' : '') . '"' . ($field['type'] === 'multi-select' ? ' multiple="multiple" size="10"' : '') . '>';
									echo '<label data-i18n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label>' . $selectTag;
										foreach($field['options'] as $val => $option) {
											$selected = '';
											if ((is_array($field['value']) && in_array($val, $field['value'])) || ($val === $field['value'])) {
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
					<span data-i18n="save"></span>
				</span>
				<span class="saving">
					<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>
					<span data-i18n="saving"></span>
				</span>
				<span class="success">
					<i class="fa fa-check"></i>
					<span data-i18n="success"></span>
				</span>
				<span class="error">
					<i class="fa fa-times"></i>
					<span data-i18n="saveerror"></span>
				</span>
			</button>
		<?php else:
		header("location: ../login");
		exit;
		endif; ?>
		</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/theme.js"></script>
	<script type="text/javascript" src="../resources/js/admin.js"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n-sub.js"></script>

</body>
</html>
