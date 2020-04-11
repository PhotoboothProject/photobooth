<?php

require_once('../lib/config.php');
require_once('../lib/db.php');
require_once('../lib/filter.php');

$images = getImagesFromDB();
$imagelist = array_reverse($images);

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">
	<meta http-equiv="refresh" content= "<?=$config['slideshow_refresh_time']?>">


	<title>Photobooth Slideshow</title>

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
	<link rel="stylesheet" href="../resources/css/slideshow.css" />
</head>

<body class="sshow">
	<div class="images" id="slideshow">
		<?php if (empty($imagelist)): ?>
		<h1 data-i18n="gallery_no_image"></h1>
		<?php else: ?>
		<?php
		if ($config['slideshow_randomPicture']):
		shuffle($imagelist);
		endif;
		?>
		<?php foreach ($imagelist as $image): ?>
		<?php

		$date = 'Photobooth Slideshow';
		if ($config['file_format_date']) {
			$dateObject = DateTime::createFromFormat('Ymd_His', substr($image, 0, strlen($image) - 4));
			if ($dateObject) {
				$date = '<i class="fa fa-clock-o"></i> ' . $dateObject->format($config['gallery']['date_format']);
			}
		}

		if ($config['slideshow_use_thumbs']) {
			$filename_photo = '../' . $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $image;
		} else {
			$filename_photo = '../' . $config['folders']['images'] . DIRECTORY_SEPARATOR . $image;
		}

		?>
		<div class="center">
			<figure>
				<img src="<?=$filename_photo?>" alt="<?=$image?>" />
				<figcaption><?=$date?></figcaption>
			</figure>
		</div>

		<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<script type="text/javascript" src="../api/config.php"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/vendor/jquery.easing.1.3.js"></script>
	<script type="text/javascript" src="../resources/js/vendor/TweenLite.min.js"></script>
	<script type="text/javascript" src="../resources/js/vendor/EasePack.min.js"></script>
	<script type="text/javascript" src="../resources/js/vendor/jquery.gsap.min.js"></script>
	<script type="text/javascript" src="../resources/js/vendor/CSSPlugin.min.js"></script>
	<script type="text/javascript" src="../resources/js/theme.js"></script>
	<script type="text/javascript" src="../resources/js/slideshow.js"></script>
	<script type="module" src="../resources/js/i18n.js"></script>
</body>
</html>
