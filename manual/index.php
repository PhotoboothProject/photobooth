<?php
session_start();

require_once('../lib/config.php');

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_manual'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['manual'])
) {
    require_once('../lib/configsetup.inc.php');
} else {
    header('location: ../login');
    exit();
}


$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$btnClass = 'btn ' . $btnShape;

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?> Manual</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../resources/css/manual.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>
<body class="manualwrapper">
	<div class="manual-panel <?php echo $uiShape; ?>">
		<h2><?=$config['ui']['branding']?> Manual</h2>
		<h3><a class="back-to-pb" href="../">Photobooth</a></h3>

		<div class="accordion">
			<form>
				<?php
					$i = 0;
					foreach($configsetup as $panel => $fields) {
						if (empty($fields['view'])) {
						   $fields['view'] = 'basic';
						};

						$open = '';
						if($i == 0){
							$open = ' open init';
						}
						echo '<div class="panel'.$open.'"';
						switch ($fields['view'])
						{
							case 'expert':
							     if ($config['adminpanel']['view'] == 'advanced') {
							     	echo ' style="display: none;"';
							     };
							case 'advanced':
							     if ($config['adminpanel']['view'] == 'basic') { 
    							     	echo ' style="display: none;"';
							     };
							case 'basic':
							     break;
						};
						echo '>';
								echo '<div class="panel-heading '.$uiShape.'">';
									echo '<h3><span class="minus">-</span><span class="plus">+</span><span data-i18n="'.$panel.'">'.$panel.'</span></h3>';
								echo '</div>';
								echo '<div class="panel-body '.$uiShape.'">';

								foreach($fields as $key => $field) {
									if ($key == 'platform' || $key == 'view') {
									   continue;
									   };

									if (! isset($field['view'])) {
						   	   		   $field['view'] = 'basic';
									   };

									switch ($field['view'])
									{
										case 'expert':
							     	     		     if ($config['adminpanel']['view'] == 'advanced') { $field['type'] = 'hidden'; };
										case 'advanced':
							     	     		     if ($config['adminpanel']['view'] == 'basic') { $field['type'] = 'hidden'; };
										case 'basic':
							     	     		     break;
									};

									switch($field['type']) {
										case 'checkbox':
											echo '<div class="form-row">';
											echo '<p><h4><span data-i18n="'.$panel.':'.$key.'">'.$panel.':'.$key.'</span></h4></p>';
											echo '<p><span data-i18n="manual:'.$panel.':'.$key.'">manual:'.$panel.':'.$key.'</span></p><hr>';
											echo '</div>';
											break;
										case 'multi-select':
										case 'range':
										case 'select':
										case 'input':
											echo '<div class="form-row">';
											echo '<p><h4><span data-i18n="'.$panel.':'.$key.'"></span></h4></p>';
											echo '<p><span data-i18n="manual:'.$panel.':'.$key.'">manual:'.$panel.':'.$key.'</span></p><hr>';
											echo '</div>';
											break;
										case 'color':
										case 'hidden':
											echo '<div class="form-row">';
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
			</br>
			<div>
				<a href="faq.php" class="<?php echo $btnClass; ?> faq-btn" title="FAQ" target="newwin"><span data-i18n="show_faq"></span> <?php echo $config['icons']['faq']; ?></a></br>
				<a href="https://photoboothproject.github.io" class="<?php echo $btnClass; ?> wiki-btn"><span data-i18n="show_wiki"></span></a>
			</div>
			</form>
		</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/manual.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
