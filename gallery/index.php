<?php

$fileRoot = '../';
require_once($fileRoot . 'lib/config.php');

$pageTitle = $config['ui']['branding'] . ' Gallery';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;
$chromaKeying = false;
$GALLERY_FOOTER = false;
$gallery_standalone = true;

include($fileRoot . 'template/components/helper/index.php');
include($fileRoot . 'template/components/main.head.php');

if($config['ui']['style'] == 'evolution') {
    $templateFolder = 'template/components/'. $config['ui']['style'] . '/';
} else {
    $templateFolder = 'template/';
}
?>
<body class="deselect">
	<div id="wrapper">
		<?php
		$gallery_path = $fileRoot;
		include($fileRoot . $templateFolder . 'gallery.template.php');
		?>
	</div>

	<script type="text/javascript">
		onStandaloneGalleryView = true;
	</script>

	<?php
		include($fileRoot . 'template/components/modal.qr.php');
		include($fileRoot . 'template/send-mail.template.php');
		include($fileRoot . 'template/components/main.footer.php');
	?>

	<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/qrcode.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php if ($config['gallery']['db_check_enabled']): ?>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php endif; ?>

	<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
