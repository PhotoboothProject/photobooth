<?php
require_once('lib/config.php');

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
		<link rel="stylesheet" href="resources/css/chromakeying.css" />
		<?php if ($config['rounded_corners']): ?>
		<link rel="stylesheet" href="resources/css/rounded.css" />
		<?php endif; ?>
	</head>
<body>
	<div class="chromawrapper">
		<div class="canvasWrapper">
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

		<div style="padding-top:10px;text-align:center;">
			<?php
				$dir = join(DIRECTORY_SEPARATOR, ['resources', 'img', 'background']) . DIRECTORY_SEPARATOR;
				$cdir = scandir($dir);
				foreach ($cdir as $key => $value) {
					if (!in_array($value, array(".","..")) && !is_dir($dir.$value)) {
						echo '<img src="'.$dir.$value.'" class="backgroundPreview" onclick="setBackgroundImage(this.src)">';
					}
				}
			?>
		</div>

		<div class="chroma-control-bar">
			<a href="#" class="btn takeChroma"><i class="fa fa-camera"></i> <span data-i18n="takePhoto"></span></a>
			<a href="#" class="btn reloadPage"><i class="fa fa-refresh"></i> <span data-i18n="reload"></span></a>
			<button hidden class="triggerChroma"></button>
		</div>
	<div>

	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="resources/js/photoinit.js"></script>
	<script type="text/javascript" src="resources/js/core.js"></script>
	<?php if ($config['chroma_keying_variant'] === 'marvinj'): ?>
	<script type="text/javascript" src="node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>
	<?php else:?>
	<script type="text/javascript" src="vendor/Seriously/seriously.js"></script>
	<script type="text/javascript" src="vendor/Seriously/effects/seriously.chroma.js"></script>
	<?php endif; ?>
	<script type="text/javascript" src="resources/js/livechroma.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js"></script>
</body>
</html>
