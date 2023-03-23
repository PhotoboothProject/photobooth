<?php

require_once('../lib/config.php');
require_once('../lib/db.php');

if ($config['database']['enabled']) {
	$images = getImagesFromDB();
} else {
	$images = getImagesFromDirectory($config['foldersAbs']['images']);
}

$imagelist = !empty($images) ? array_reverse($images) : $images;

if (!empty($imagelist) && $config['slideshow']['randomPicture']) {
    shuffle($imagelist);
}

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$btnClass = 'btn btn--' . $config['ui']['button'];

$GALLERY_FOOTER = false;
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

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
	<link rel="stylesheet" href="../node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" href="../node_modules/material-icons/css/material-icons.css">
	<link rel="stylesheet" href="../vendor/PhotoSwipe/dist/photoswipe.css" />
	<link rel="stylesheet" href="../resources/css/<?php echo $config['ui']['style']; ?>_style.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php if (is_file("../private/overrides.css")): ?>
	<link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="deselect">
	<div id="wrapper">

<div id="gallery" class="gallery">
	<div class="gallery__inner">
		<div class="gallery__header">
			<h1><span data-i18n="slideshow"></span></h1>
		</div>
		<div class="gallery__body" id="galimages">
			<?php if (empty($imagelist)): ?>
			<h1 style="text-align:center" data-i18n="gallery_no_image"></h1>
			<?php else: ?>
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
                    $date = '<i class="' . $config['icons']['date'] . '"></i> ' . $dateObject->format($config['gallery']['date_format']);
                }
            }

            $filename_photo = '../' . $config['foldersRoot']['images'] . DIRECTORY_SEPARATOR . $image;
            if (is_readable($filename_photo)) {
                $filename_thumb = '../' . $config['foldersRoot']['thumbs'] . DIRECTORY_SEPARATOR . $image;

                if (!is_readable($filename_thumb)) {
                    $filename_thumb = '../' . $config['foldersRoot']['images'] . DIRECTORY_SEPARATOR . $image;
                }
                $imageinfo = getimagesize($filename_photo);
                $imageinfoThumb = getimagesize($filename_thumb);
            ?>

			<a href="<?=$filename_photo?>" class="gallery__img rotaryfocus" data-size="<?=$imageinfo[0]?>x<?=$imageinfo[1]?>" data-pswp-width="<?=$imageinfo[0]?>" data-pswp-height="<?=$imageinfo[1]?>"
				data-med="<?=$filename_thumb?>" data-med-size="<?=$imageinfoThumb[0]?>x<?=$imageinfoThumb[1]?>">
				<figure class="<?php echo $uiShape; ?>">
					<img class="<?php echo $uiShape; ?>" src="<?=$filename_thumb?>" alt="<?=$image?>" />
                    <?php if ($config['gallery']['figcaption']): ?>
                    <figcaption class="<?php echo $uiShape; ?>"><?=$date?></figcaption>
                    <?php endif; ?>
				</figure>
			</a>
	<?php } ?>
				<?php endforeach; ?>
				<?php endif; ?>
		</div>
	</div>
</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../vendor/PhotoSwipe/dist/umd/photoswipe.umd.min.js"></script>
	<script type="text/javascript" src="../vendor/PhotoSwipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>
	<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/slideshow.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
</body>
</html>
