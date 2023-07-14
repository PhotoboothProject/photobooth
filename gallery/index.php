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

include($fileRoot . 'template/components/main.head.php');
?>
<body class="deselect">
	<div id="wrapper">
		<?php include($fileRoot . 'template/gallery.template.php'); ?>
    </div>

	<script type="text/javascript">
		onStandaloneGalleryView = true;
	</script>

	<?php
	    include($fileRoot . 'template/send-mail.template.php');
	    include($fileRoot . 'template/components/main.footer.php');
	?>

	<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php if ($config['gallery']['db_check_enabled']): ?>
	<script type="text/javascript" src="<?=$fileRoot?>resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<?php endif; ?>

	<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
