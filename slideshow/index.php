<?php

require_once('../lib/config.php');
require_once('../lib/db.php');
require_once('../lib/filter.php');

if ($config['database']['enabled']) {
	$images = getImagesFromDB();
} else {
	$images = getImagesFromDirectory($config['foldersAbs']['images']);
}
$imagelist = array_reverse($images);

$uiShape = 'shape--' . $config['ui']['style'];
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">
	<meta http-equiv="refresh" content= "<?=$config['slideshow']['refreshTime']?>">


	<title><?=$config['ui']['branding']?> Slideshow</title>

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
	<link rel="stylesheet" href="../resources/css/slideshow.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="sshow">
	<div class="images" id="slideshow">
		<?php if (empty($imagelist)): ?>
		<h1 data-i18n="gallery_no_image"></h1>
		<?php else: ?>
		<?php
		if ($config['slideshow']['randomPicture']):
		shuffle($imagelist);
		endif;
		?>
		<?php foreach ($imagelist as $image): ?>
		<?php

		$date = $config['ui']['branding'] . ' Slideshow';
		if ($config['picture']['naming'] === 'dateformatted' && $config['gallery']['show_date']) {
			if ($config['database']['file'] != 'db') {
				$db = strlen($config['database']['file']);
				$name = substr($image, ++$db);
			} else {
				$name = $image;
			}
			$dateObject = DateTime::createFromFormat('Ymd_His', substr($name, 0, strlen($name) - 4));
			if ($dateObject) {
				$date = '<i class="fa fa-clock-o"></i> ' . $dateObject->format($config['gallery']['date_format']);
			}
		}

		if ($config['slideshow']['use_thumbs']) {
			$filename_photo = '../' . $config['foldersRoot']['thumbs'] . DIRECTORY_SEPARATOR . $image;
			if (!is_readable($filename_photo)) {
				$filename_photo = '../' . $config['foldersRoot']['images'] . DIRECTORY_SEPARATOR . $image;
			}
		} else {
			$filename_photo = '../' . $config['foldersRoot']['images'] . DIRECTORY_SEPARATOR . $image;
		}

		if (is_readable($filename_photo)) {
		?>
		<div class="center">
			<figure>
				<img class="<?php echo $uiShape; ?>" src="<?=$filename_photo?>" alt="<?=$image?>" />
                <?php if ($config['gallery']['figcaption']): ?>
                <figcaption class="<?php echo $uiShape; ?>"><?=$date?></figcaption>
                <?php endif; ?>
			</figure>
		</div>
		<?php } ?>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/theme.js"></script>
	<script type="text/javascript" src="../resources/js/slideshow.js"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js"></script>
</body>
</html>
