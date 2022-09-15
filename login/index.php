<?php
session_start();

require_once('../lib/config.php');

// LOGIN
$username = $config['login']['username'];
$hashed_password = $config['login']['password'];
$error = false;

if (isset($_POST['submit'])) {
    if (isset($_POST['username']) && $_POST['username'] == $username && isset($_POST['password']) && password_verify($_POST["password"], $hashed_password)) {
        //IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
        $_SESSION['auth'] = true;
    } else {
        // DISPLAY FORM WITH ERROR
        $error = true;
    }
}
// END LOGIN

$btnClass = 'btn btn--login shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?> Login</title>

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
	<link rel="stylesheet" href="../resources/css/login.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="loginbody">
	<div class="login-panel <?php echo $uiShape; ?>">
		<h2><?=$config['ui']['branding']?> Login</h2>
		<hr>
		<?php if($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<form method='post' class="login">
			<label for="username"><span data-i18n="login_username"></span></label>
			<input type="text" name="username" id="username" autocomplete="on" required>
			<label for="password"><span data-i18n="login_password"></span></label>
			</br>
			<input type="password" name="password" id="password" autocomplete="on" required>
			<span toggle="#password" class="password-toggle fa fa-eye"></span>
			<p><input type="submit" name="submit" value="Login" class="btn btn--tiny btn--flex"></p>
			<?php if ($error !== false) {
				echo '<p style="color: red;"><span data-i18n="login_invalid"></span></p>';
			} ?>
		</form>
		<hr>
		<?php endif; ?>
		<?php if(!$config['protect']['admin'] || (!$config['protect']['localhost_admin'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<p><a href="../admin" class="<?php echo $btnClass; ?>"><i class="fa fa-cog"></i> <span data-i18n="admin_panel"></span></a></p>
		<p><a href="../dependencies.php" class="<?php echo $btnClass; ?>"><i class="fa fa-list-ul"></i> <span data-i18n="dependencies_check"></span></a></p>
		<?php endif; ?>
		<?php if(!$config['protect']['update'] || (!$config['protect']['localhost_update'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<p><a href="../update.php" class="<?php echo $btnClass; ?>"><i class="fa fa-tasks"></i> <span data-i18n="updater"></span></a></p>
		<?php endif; ?>
		<p><a href="../gallery.php" class="<?php echo $btnClass; ?>"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a></p>
		<p><a href="../slideshow" class="<?php echo $btnClass; ?>"><i class="fa fa-play"></i> <span data-i18n="slideshow"></span></a></p>
		<?php if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<p><a href="../livechroma.php" class="<?php echo $btnClass; ?>"><i class="fa fa-paint-brush"></i> <span data-i18n="livechroma"></span></a></p>
		<?php endif; ?>
		<?php if(!$config['protect']['manual'] || (!$config['protect']['localhost_manual'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<p><a href="../manual/faq.php" class="<?php echo $btnClass; ?>" title="FAQ" target="newwin"><i class="fa fa-question-circle" aria-hidden="true"></i> <span data-i18n="show_faq"></span></a></p>
		<p><a href="../manual" class="<?php echo $btnClass; ?>" title="Manual" target="newwin"><i class="fa fa-info-circle" aria-hidden="true"></i> <span data-i18n="show_manual"></span></a></p>
		<p><a href="https://t.me/PhotoboothGroup" class="<?php echo $btnClass; ?>" title="Telegram" target="newwin"><i class="fa fa-telegram" aria-hidden="true"></i> <span data-i18n="telegram"></span></a></p>
		<?php endif; ?>
		<p><a href="./" class="<?php echo $btnClass; ?>"><i class="fa fa-refresh"></i> <span data-i18n="reload"></span></a></p>
		<?php if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
		<p><a href="../" class="<?php echo $btnClass; ?>" ><i class="fa fa-times"></i> <span data-i18n="close"></span></a></p>
		<?php endif; ?>
		<?php if(isset($_SESSION['auth']) && $_SESSION['auth'] === true): ?>
		<p><a href="logout.php" class="<?php echo $btnClass; ?>"><i class="fa fa-sign-out"></i> <span data-i18n="logout"></span></a></p>
		<?php endif; ?>
	</div>

	<div id="adminsettings">
		<div style="position:absolute; bottom:0; right:0;">
			<img src="../resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()" />
		</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/adminshortcut.js"></script>
	<script type="text/javascript" src="../resources/js/login.js"></script>
	<script type="text/javascript" src="../resources/js/theme.js"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js"></script>
</body>
</html>
