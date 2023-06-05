<?php

require_once('lib/config.php');
require_once('lib/db.php');

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

// Check if there is a request for the status of the database
if (isset($_GET['status'])){
	// Request for DB-Status,
	// Currently reports back the DB-Size to give the Client the ability
	// to detect changes
	$resp = array('dbsize'=>$database->getDBSize());
	exit(json_encode($resp));
}

if ($config['database']['enabled']) {
	$images = $database->getContentFromDB();
} else {
	$images = $database->getFilesFromDirectory();
}

$imagelist = $config['gallery']['newest_first'] === true && !empty($images) ? array_reverse($images) : $images;

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$btnClass = 'btn btn--' . $config['ui']['button'];

$fileRoot = '';
$pageTitle = $config['ui']['branding'] . ' Gallery';
$GALLERY_FOOTER = false;
?>
<!DOCTYPE html>
<html>

<head>
	<?php include('template/components/mainHead.php'); ?>

	<link rel="stylesheet" href="node_modules/photoswipe/dist/photoswipe.css" />
	<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_style.css?v=<?php echo $config['photobooth']['version']; ?>" />
    <?php if ($config['gallery']['bottom_bar']): ?>
        <link rel="stylesheet" href="resources/css/photoswipe-bottom.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
	<?php if (is_file("private/overrides.css")): ?>
	<link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
	<?php endif; ?>
</head>

<body class="deselect">
	<div id="wrapper">
		<?php include('template/gallery.template.php'); ?>
	</div>

	<?php include('template/send-mail.template.php'); ?>

	<div class="modal" id="print_mesg">
		<div class="modal__body"><span data-i18n="printing"></span></div>
	</div>

	<div class="modal" id="modal_mesg"></div>

	<div id="adminsettings">
		<div style="position:absolute; bottom:0; right:0;">
			<img src="resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()" />
		</div>
	</div>

	<script type="text/javascript">
		onStandaloneGalleryView = true;
	</script>
    <?php include('template/components/mainFooter.php'); ?>
	<script type="text/javascript" src="resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>
	<script type="text/javascript" src="node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>
	<script type="text/javascript" src="resources/js/remotebuzzer_client.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/photoswipe.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php if ($config['gallery']['db_check_enabled']): ?>
	<script type="text/javascript" src="resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php endif; ?>

	<?php require_once('lib/services_start.php'); ?>
</body>
</html>
