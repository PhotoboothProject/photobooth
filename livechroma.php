<?php
session_start();

require_once 'lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['index'])
) {
    require_once 'lib/db.php';

    if ($config['database']['enabled']) {
        $images = getImagesFromDB();
    } else {
        $images = getImagesFromDirectory($config['foldersAbs']['images']);
    }
    $imagelist = $config['gallery']['newest_first'] === true ? array_reverse($images) : $images;

    if ($config['ui']['style'] === 'modern') {
        $btnClass1 = 'round-btn';
        $btnClass2 = 'round-btn';
    } else {
        $btnClass1 = 'btn btn--small btn--flex';
        $btnClass2 = 'btn';
    }
} else {
    header('location: login');
    exit();
}
?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
		<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
		<meta name="theme-color" content="<?=$config['colors']['primary']?>">

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
		<link rel="stylesheet" href="vendor/PhotoSwipe/dist/photoswipe.css" />
		<link rel="stylesheet" href="vendor/PhotoSwipe/dist/default-skin/default-skin.css" />
		<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_live_chromakeying.css" />
		<?php if ($config['gallery']['bottom_bar']): ?>
		<link rel="stylesheet" href="resources/css/photoswipe-bottom.css" />
		<?php endif; ?>
		<?php if ($config['ui']['rounded_corners']): ?>
		<link rel="stylesheet" href="resources/css/rounded.css" />
		<?php endif; ?>
		<?php if (is_file("private/overrides.css")): ?>
		<link rel="stylesheet" href="private/overrides.css" />
		<?php endif; ?>
	</head>
<body>
	<div class="chromawrapper">
	<div class="rotarygroup" id="start">
		<div class="top-bar">
			<?php if (!$config['live_keying']['enabled']): ?>
			<a href="index.php" class="<?php echo $btnClass1; ?> closebtn rotaryfocus"><i class="fa fa-times"></i></a>
			<?php endif; ?>

			<?php if ($config['gallery']['enabled']): ?>
			<a href="#" class="<?php echo $btnClass1 ?> gallerybtn rotaryfocus"><i class="fa fa-th"></i> <span data-i18n="gallery"></span></a>
			<?php endif; ?>

		</div>

		<div class="canvasWrapper initial">
			<canvas id="mainCanvas"></canvas>
		</div>

		<div class="chromaNote">
			<span data-i18n="chromaInfoBefore"></span>
		</div>

		<div class="stages" id="loader">
			<div class="loaderInner">
				<div class="spinner">
					<i class="fa fa-cog fa-spin"></i>
				</div>

				<div id="ipcam--view"></div>

				<video id="video--view" autoplay playsinline></video>

				<div id="counter">
					<canvas id="video--sensor"></canvas>
				</div>
				<div class="cheese"></div>
				<div class="loading"></div>
			</div>
		</div>

		<!-- Result Page -->
		<div class="stages" id="result"></div>

		<div class="backgrounds"> 
			<?php
				$dir = $config['keying']['background_path'] . DIRECTORY_SEPARATOR;
				$cdir = scandir($dir);
				foreach ($cdir as $key => $value) {
					if (!in_array($value, array(".","..")) && !is_dir($dir.$value)) {
						echo '<img src="'.$dir.$value.'" class="backgroundPreview rotaryfocus" onclick="setBackgroundImage(this.src)">';
					}
				}
			?>
		</div>

		<div class="chroma-control-bar">
			<a href="#" class="<?php echo $btnClass2; ?> takeChroma rotaryfocus"><i class="fa fa-camera"></i> <span data-i18n="takePhoto"></span></a>
			<?php if ($config['picture']['allow_delete']): ?>
			<a href="#" class="<?php echo $btnClass2; ?> deletebtn"><i class="fa fa-trash"></i> <span data-i18n="delete"></span></a>
			<?php endif; ?>
			<a href="#" class="reloadPage <?php echo $btnClass2; ?> rotaryfocus"><i class="fa fa-refresh"></i> <span data-i18n="reload"></span></a>
		</div>
	</div>
	<div class="rotarygroup">

	<div id="wrapper">
		<?php include('template/gallery.template.php'); ?>
	</div>
	<?php include('template/pswp.template.php'); ?>

	<div class="send-mail">
		<i class="fa fa-times" id="send-mail-close"></i>
		<p data-i18n="insertMail"></p>
		<form id="send-mail-form" style="margin: 0;">
			<input class="mail-form-input" size="35" type="email" name="sendTo">
			<input id="mail-form-image" type="hidden" name="image" value="">

			<?php if ($config['mail']['send_all_later']): ?>
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

	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="vendor/PhotoSwipe/dist/photoswipe.min.js"></script>
	<script type="text/javascript" src="vendor/PhotoSwipe/dist/photoswipe-ui-default.min.js"></script>
    <script type="text/javascript" src="resources/js/tools.js"></script>
	<script type="text/javascript" src="resources/js/photoinit.js"></script>
	<script type="text/javascript" src="resources/js/core.js"></script>
	<?php if ($config['keying']['variant'] === 'marvinj'): ?>
	<script type="text/javascript" src="node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>
	<?php else:?>
	<script type="text/javascript" src="vendor/Seriously/seriously.js"></script>
	<script type="text/javascript" src="vendor/Seriously/effects/seriously.chroma.js"></script>
	<?php endif; ?>
	<script type="text/javascript" src="resources/js/livechroma.js"></script>
	<script type="text/javascript" src="resources/js/remotebuzzer_client.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js"></script>

	<?php require_once('lib/services_start.php'); ?>

	<script type="text/javascript">
		onLiveChromaKeyingView = true;
	</script>

</body>
</html>
