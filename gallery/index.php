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
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = true;
$remoteBuzzer = true;
$chromaKeying = false;
$GALLERY_FOOTER = false;

include($fileRoot . 'template/components/mainHead.php');
?>
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
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php if ($config['gallery']['db_check_enabled']): ?>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php endif; ?>

	<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
