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

	<title>Photobooth</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../resources/css/style.css" />
	<link rel="stylesheet" type="text/css" href="../resources/css/admin.css" />
</head>
<body class="deselect">
<div id="wrapper" class="adminbg" style="overflow-y: auto;">
	<div class="admin-panel">
		<h2><a class="back-to-pb" href="../">Photobooth</a></h2>
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
		<div id="checkVersion">
			<p><a href="#" class="btn btn--tiny btn--flex"><span data-l10n="check_version"></span></a></p>
		</div>
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
								case 'multi-select':
								case 'select':
									$selectTag = '<select name="'.$field['name'] . ($field['type'] === 'multi-select' ? '[]' : '') . '"' . ($field['type'] === 'multi-select' ? ' multiple="multiple" size="10"' : '') . '>';
									echo '<label data-l10n="'.$panel.'_'.$key.'">'.$panel.'_'.$key.'</label>' . $selectTag;
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
<script type="text/javascript" src="../api/config.php"></script>
<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="../resources/js/l10n.js"></script>
<script type="text/javascript" src="../resources/js/theme.js"></script>
<script type="text/javascript" src="../resources/js/admin.js"></script>
<script type="text/javascript" src="../resources/lang/<?php echo $config['language']; ?>.js"></script>

</body>
</html>
