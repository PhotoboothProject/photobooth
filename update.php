<?php
session_start();

require_once('lib/config.php');

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_update'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['update'])
) {

    // nothing for now

} else {
    header('location: login');
    exit();
}

$uiShape = 'shape--' . $config['ui']['style'];
$btnShape = 'shape--' . $config['ui']['button'];
$btnClass = 'btn ' . $btnShape;
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?></title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="resources/img/favicon-16x16.png">
	<link rel="manifest" href="resources/img/site.webmanifest">
	<link rel="mask-icon" href="resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_style.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<link rel="stylesheet" href="resources/css/update.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("private/overrides.css")): ?>
	<link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="updatewrapper">

	<div class="white-box <?php echo $uiShape; ?>" id="white-box">
		<h2><?=$config['ui']['branding']?> Updater (experimental)</h2>
		<p><span data-i18n="os_check"></span></p>
	</div>

	<a href="#" class="gitCommit <?php echo $btnClass; ?>"><i class="fa fa-play-circle"></i> <span data-i18n="update_git_commit"></span></a>

	<a href="#" class="updateDev <?php echo $btnClass; ?>"><i class="fa fa-play-circle"></i> <span data-i18n="update_to_dev"></span></a>

	<a href="#" class="updateStable <?php echo $btnClass; ?>"><i class="fa fa-play-circle"></i> <span data-i18n="update_to_stable"></span></a>

	<div>
		<a href="./" class="btn <?php echo $btnShape; ?>"><i class="fa fa-home"></i> <span data-i18n="home"></span></a>

		<a href="admin" class="btn <?php echo $btnShape; ?>"><i class="fa fa-cog"></i> <span data-i18n="admin_panel"></span></a>
	</div>

	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/update.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
