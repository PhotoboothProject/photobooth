<?php

$fileRoot = '../';
require_once($fileRoot . 'lib/config.php');
require_once($fileRoot . 'lib/db.php');

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

if ($config['database']['enabled']) {
	$images = $database->getContentFromDB();
} else {
	$images = $database->getFilesFromDirectory();
}

$imagelist = $config['gallery']['newest_first'] === true && !empty($images) ? array_reverse($images) : $images;

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$btnClass = 'btn btn--' . $config['ui']['button'];

$pageTitle = $config['ui']['branding'] . ' Gallery';
$GALLERY_FOOTER = false;
?>
<!DOCTYPE html>
<html>

<head>
	<?php include($fileRoot . 'template/components/mainHead.php'); ?>

	<link rel="stylesheet" href="<?=$fileRoot?>node_modules/photoswipe/dist/photoswipe.css" />
	<link rel="stylesheet" href="<?=$fileRoot?>resources/css/<?php echo $config['ui']['style']; ?>_style.css?v=<?php echo $config['photobooth']['version']; ?>" />
    <?php if ($config['gallery']['bottom_bar']): ?>
        <link rel="stylesheet" href="<?=$fileRoot?>resources/css/photoswipe-bottom.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
	<?php if (is_file($fileRoot . 'private/overrides.css')): ?>
	<link rel="stylesheet" href="<?=$fileRoot?>private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="deselect">
	<div id="wrapper">
		<?php include($fileRoot . 'template/gallery.template.php'); ?>
	</div>

	<?php include($fileRoot . 'template/send-mail.template.php'); ?>

	<div class="modal" id="print_mesg">
		<div class="modal__body"><span data-i18n="printing"></span></div>
	</div>

	<div class="modal" id="modal_mesg"></div>

	<div id="adminsettings">
		<div style="position:absolute; bottom:0; right:0;">
			<img src="<?=$fileRoot?>resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()" />
		</div>
	</div>

	<script type="text/javascript">
		onStandaloneGalleryView = true;
	</script>
	<?php include($fileRoot . 'template/components/mainFooter.php'); ?>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>
	<script type="text/javascript" src="<?=$fileRoot?>node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/remotebuzzer_client.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/photoswipe.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php if ($config['gallery']['db_check_enabled']): ?>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php endif; ?>

	<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
