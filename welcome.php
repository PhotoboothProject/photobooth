<?php
require_once 'lib/config.php';
require_once 'lib/helper.php';

if (!is_file('.skip_welcome')) {
    touch('.skip_welcome');
}


$URL = Photobooth::get_url();
$PHOTOBOOTH_HOME = getcwd();

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

	<title>Welcome to <?=$config['ui']['branding']?></title>

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
	<link rel="stylesheet" href="node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" href="node_modules/material-icons/css/material-icons.css">
	<link rel="stylesheet" href="resources/css/welcome.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("private/overrides.css")): ?>
	<link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body>
	<div id="wrapper" class="welcome">
		<h1>Welcome to your own Photobooth</h1>
		<p></p>
		<p>OpenSource Photobooth web interface for Linux and Windows.</p>
		<p></p>
		<p>Photobooth was initally developped by Andre Rinas especially to run on a Raspberry Pi.<br>
		In 2019 Andreas Blaesius picked up the work and continued to work on the source.</p>
		<p>With the help of the community Photobooth grew to a powerfull Photobooth software with a lot of features and possibilities.<br>
		By a lot of features, we mean a lot (!!!) and you might have some questions - now or later. You can find a lot of useful information
		<a href="https://photoboothproject.github.io" target="_blank" rel="noopener noreferrer">https://photoboothproject.github.io</a> or at the <a href="https://t.me/PhotoboothGroup" target="_blank" rel="noopener noreferrer">Telegram group</a>.</p>
		<p></p>
		<h3>Here are some basic information for you:</h3>
		<p><b>Location of your Photobooth installation:</b> <?=$PHOTOBOOTH_HOME?><br>
		<i>All files and folders inside this path belong to the Webserver user "www-data".</i><p>
		<p><b>Images can be found at:</b> <?=$config['foldersAbs']['images']?></p>
		<p><b>Databases are placed at:</b> <?=$config['foldersAbs']['data']?></p>
		<p><b>Add your own files (e.g. background images, frames, overrides.css) inside:</b> <?=$PHOTOBOOTH_HOME . DIRECTORY_SEPARATOR . "private"?><br>
		<i>All files and folders inside this path will be ignored on git and won't cause trouble while updating Photobooth.</i></p>
		<p>You can change the settings and look of Photobooth using the Admin panel at <a href="admin" target="_blank" rel="noopener noreferrer"><?=$URL;?>/admin</a>.<br>
		A standalone gallery can be found at <a href="gallery.php" target="_blank" rel="noopener noreferrer"><?=$URL;?>/gallery.php</a>.<br>
		A standalone slideshow can be found at <a href="slideshow" target="_blank" rel="noopener noreferrer"><?=$URL;?>/slideshow</a>.<br>
		An integrated FAQ to answer a lot of questions can be found at <a href="faq" target="_blank" rel="noopener noreferrer"><?=$URL;?>/faq</a>.</p>
		<p></p>
		<p>You are missing some translation or your language isn't supported yet? Don't worry! You can request new language support at <a href="https://github.com/PhotoboothProject/photobooth/issues" target="_blank" rel="noopener noreferrer">GitHub</a>,
		you can translate Photobooth at <a href="https://crowdin.com/project/photobooth" target="_blank" rel="noopener noreferrer">Crowdin</a>.</p>
		<p></p>
		<p class="security-advice <?php echo $uiShape; ?>"><b>Security advice</b><br><br>
		Photobooth is not hardened against any kind of <i>targeted</i> attacks.<br>
		It uses user defined commands for tasks like taking photos and is allowed to replace its own files for easy updating.<br>
		Because of this it's not advised to operate Photobooth in an untrusted network and<br>
		<b>you should absolutely not make Photobooth accessible through the internet without heavy modifications!</b></p>
		<p></p>
		<p>Thanks for the reading! Enjoy your Photobooth!</p>
		<p><a href="<?=$URL;?>" class="btn btn--flex <?php echo $btnShape; ?>">Start Photobooth</a></p>
	</div>

	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
</body>
</html>
