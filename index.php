<?php
session_start();

require_once('lib/config.php');
require_once('lib/db.php');
require_once('lib/filter.php');

$images = getImagesFromDB();
$imagelist = ($config['newest_first'] === true) ? array_reverse($images) : $images;
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
	<link rel="stylesheet" href="node_modules/photoswipe/dist/photoswipe.css" />
	<link rel="stylesheet" href="node_modules/photoswipe/dist/default-skin/default-skin.css" />
	<link rel="stylesheet" href="resources/css/style.css" />
	<?php if ($config['gallery_bottom_bar']): ?>
	<link rel="stylesheet" href="resources/css/photoswipe-bottom.css" />
	<?php endif; ?>
	<?php if ($config['rounded_corners']): ?>
	<link rel="stylesheet" href="resources/css/rounded.css" />
	<?php endif; ?>
</head>

<video id="video--preview" autoplay playsinline></video>
<body class="deselect">
	<div id="wrapper">
	<?php if( !$config['login_enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true || !$config['protect_index'])): ?>
		<!-- Start Page -->
		<div class="stages" id="start">
			<?php if ($config['show_gallery']): ?>
			<a class="gallery-button btn" href="#"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
			<?php endif; ?>

			<div class="startInner">
				<?php if ($config['is_event']): ?>
				<div class="names">
					<hr class="small" />
					<hr>
					<div>
						<h1><?=$config['event']['textLeft']?>
							<i class="fa <?=$config['event']['symbol']?>" aria-hidden="true"></i>
							<?=$config['event']['textRight']?>
							<br>
							<?=$config['start_screen_title']?>
						</h1>
						<h2><?=$config['start_screen_subtitle']?></h2>
					</div>
					<hr>
					<hr class="small" />
				</div>
				<?php else: ?>
				<div class="names">
					<hr class="small" />
					<hr>
					<div>
						<h1><?=$config['start_screen_title']?></h1>
						<h2><?=$config['start_screen_subtitle']?></h2>
					</div>
					<hr>
					<hr class="small" />
				</div>
				<?php endif; ?>

				<?php if ($config['force_buzzer']): ?>
				<div id="useBuzzer">
						<span data-i18n="use_button"></span>
				</div>
				<?php else: ?>
					<?php if ($config['use_collage']): ?>
					<a href="#" class="btn takeCollage"><i class="fa fa-th-large"></i> <span
							data-i18n="takeCollage"></span></a>
					<?php endif; ?>

					<a href="#" class="btn takePic"><i class="fa fa-camera"></i> <span data-i18n="takePhoto"></span></a>
				<?php endif; ?>
			</div>

			<?php if ($config['show_fork']): ?>
			<a href="https://github.com/andi34/photobooth" class="github-fork-ribbon" data-ribbon="Fork me on GitHub">Fork me on GitHub</a>
			<?php endif; ?>

			<?php if($config['cups_button']): ?>
				<a id="cups-button" class="btn cups-button" href="#" target="newwin"><span>CUPS</span></a>
			<?php endif; ?>
			<?php if($config['toggle_fs_button']): ?>
				<a href="#" id="fs-button" class="btn btn--small fs-button"><i class="fa fa-arrows-alt"></i> <span data-i18n="toggleFullscreen"></span></a>
			<?php endif; ?>
		</div>

		<!-- image Filter Pane -->
		<?php if ($config['use_filter']): ?>
		<div id="mySidenav" class="dragscroll sidenav">
			<a href="#" class="closebtn"><i class="fa fa-times"></i></a>

			<?php foreach(AVAILABLE_FILTERS as $filter => $name): ?>
				<?php if (!in_array($filter, $config['disabled_filters'])): ?>
					<div id="<?=$filter?>" class="filter <?php if($config['default_imagefilter'] === $filter)echo 'activeSidenavBtn'; ?>">
						<a class="btn btn--small" href="#"><?=$name?></a>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<!-- Loader -->
		<div class="stages" id="loader">
			<div class="loaderInner">
				<div class="spinner">
					<i class="fa fa-cog fa-spin"></i>
				</div>

				<div id="ipcam--view"></div>

				<video id="video--view" autoplay playsinline></video>

				<div id="counter">
					<canvas id="video--sensor"</canvas>
				</div>
				<div class="cheese"></div>
				<div class="loading"></div>
			</div>
		</div>

		<!-- Result Page -->
		<div class="stages" id="result">
			<a href="#" class="btn homebtn"><i class="fa fa-home"></i> <span data-i18n="home"></span></a>
			<div class="resultInner hidden">
				<?php if ($config['show_gallery']): ?>
				<a href="#" class="btn gallery-button"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
				<?php endif; ?>

				<?php if ($config['use_qr']): ?>
				<a href="#" class="btn qrbtn"><i class="fa fa-qrcode"></i> <span data-i18n="qr"></span></a>
				<?php endif; ?>

				<?php if ($config['use_mail']): ?>
				<a href="#" class="btn mailbtn"><i class="fa fa-envelope"></i> <span data-i18n="mail"></span></a>
				<?php endif; ?>

				<?php if ($config['use_print']): ?>
				<a href="#" class="btn printbtn"><i class="fa fa-print"></i> <span data-i18n="print"></span></a>
				<?php endif; ?>

				<?php if (!$config['force_buzzer']): ?>
					<a href="#" class="btn newpic"><i class="fa fa-camera"></i> <span data-i18n="newPhoto"></span></a>

					<?php if ($config['use_collage']): ?>
					<a href="#" class="btn newcollage"><i class="fa fa-th-large"></i> <span
							data-i18n="newCollage"></span></a>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ($config['use_filter']): ?>
				<a href="#" class="btn imageFilter"><i class="fa fa-magic"></i> <span data-i18n="selectFilter"></span></a>
				<?php endif; ?>

				<?php if ($config['allow_delete']): ?>
				<a href="#" class="btn deletebtn"><i class="fa fa-trash"></i> <span data-i18n="delete"></span></a>
				<?php endif; ?>
			</div>

			<?php if ($config['use_qr']): ?>
			<div id="qrCode" class="modal">
				<div class="modal__body"></div>
			</div>
			<?php endif; ?>
		</div>

		<?php if ($config['show_gallery']): ?>
		<?php include('template/gallery.template.php'); ?>
		<?php endif; ?>
	</div>

	<?php include('template/pswp.template.php'); ?>

	<div class="send-mail">
		<i class="fa fa-times" id="send-mail-close"></i>
		<p data-i18n="insertMail"></p>
		<form id="send-mail-form" style="margin: 0;">
			<input class="mail-form-input" size="35" type="email" name="sendTo">
			<input id="mail-form-image" type="hidden" name="image" value="">

			<?php if ($config['send_all_later']): ?>
				<input type="checkbox" id="mail-form-send-link" name="send-link" value="yes">
				<label data-i18n="sendAllMail" for="mail-form-send-link"></label>
			<?php endif; ?>

			<button class="mail-form-input btn" name="submit" type="submit" value="Send"><span data-i18n="send"></span></button>
		</form>

		<div id="mail-form-message" style="max-width: 75%"></div>
	</div>

	<div class="modal" id="print_mesg">
		<div class="modal__body"><span data-i18n="printing"></span></div>
	</div>

	<div id="adminsettings">
		<div style="position:absolute; bottom:0; right:0;">
			<img src="resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()" />
		</div>
	<?php else:
	header("location: login");
	exit;
	endif; ?>
	</div>

	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="resources/js/adminshortcut.js"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="resources/js/vendor/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="resources/js/vendor/TweenLite.min.js"></script>
	<script type="text/javascript" src="resources/js/vendor/EasePack.min.js"></script>
	<script type="text/javascript" src="resources/js/vendor/jquery.gsap.min.js"></script>
	<script type="text/javascript" src="resources/js/vendor/CSSPlugin.min.js"></script>
	<script type="text/javascript" src="node_modules/photoswipe/dist/photoswipe.min.js"></script>
	<script type="text/javascript" src="node_modules/photoswipe/dist/photoswipe-ui-default.min.js"></script>
	<script type="text/javascript" src="resources/js/photoinit.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script type="text/javascript" src="resources/js/core.js"></script>
	<script type="module" src="resources/js/i18n.js"></script>

</body>
</html>
