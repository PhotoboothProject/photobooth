<?php

require_once('../lib/config.php');
require_once('../lib/db.php');

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;
if ($config['database']['enabled']) {
    $images = $database->getContentFromDB();
} else {
    $images = $database->getFilesFromDirectory();
}

$imagelist = !empty($images) ? array_reverse($images) : $images;

if (!empty($imagelist) && $config['slideshow']['randomPicture']) {
    shuffle($imagelist);
}

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$btnClass = 'btn btn--' . $config['ui']['button'];

$fileRoot = '../';
$pageTitle = $config['ui']['branding'] . ' Slideshow';
$GALLERY_FOOTER = false;
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('../template/components/mainHead.php'); ?>

	<link rel="stylesheet" href="../node_modules/photoswipe//dist/photoswipe.css" />
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
        <?php include('../template/components/galImages.php'); ?>
</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>
	<script type="text/javascript" src="../node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>
	<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/slideshow.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
</body>
</html>
