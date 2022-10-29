<?php
session_start();

require_once '../lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    require_once '../lib/diskusage.php';
} else {
    header('location: ../login');
    exit();
}

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?> Disk Usage</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" href="../resources/css/login.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="loginbody">
	<div class="login-panel <?php echo $uiShape; ?>">
		<h2><?=$config['ui']['branding']?> <span data-i18n="disk_usage"></span></h2>
		<a class="btn btn--tiny btn--flex <?php echo $btnShape; ?> back-to-admin" href="./"><?php echo $config['icons']['admin_back_short'] ?></a>
		<button class="btn btn--tiny btn--flex <?php echo $btnShape; ?> download-zip-btn ">
			<span data-i18n="download_zip"></span>
		</button>
		<hr>
<?php
    foreach ($config['foldersAbs'] as $key => $folder) {
        $path = $config['foldersAbs'][$key];
        $disk_used = foldersize($config['foldersAbs'][$key]);

        echo('<h3><span data-i18n="path"></span> ' . $folder . '</h3>');
        echo('<b><span data-i18n="foldersize"></span></b> ' . format_size($disk_used) . '<br>');
        echo('<b><span data-i18n="filecount"></span></b> ' . get_filecount($path) . '<br><hr>');

    }
?>
	</div>

	<div id="adminsettings">
		<div style="position:absolute; bottom:0; right:0;">
			<img src="../resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()" />
		</div>
	</div>

	<div class="modal" id="save_mesg">
		<div class="modal__body" id="save_mesg_text"><span data-i18n="saving"></span></div>
	</div>

	<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/diskusage.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="module" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
