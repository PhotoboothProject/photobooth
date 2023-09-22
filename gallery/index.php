<?php

require_once '../lib/boot.php';

use Photobooth\Utility\PathUtility;

$pageTitle = $config['ui']['branding'] . ' Gallery';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = true;
$randomImage = false;
$remoteBuzzer = true;
$chromaKeying = false;
$GALLERY_FOOTER = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>
<body class="deselect">
    <div id="wrapper">
        <?php include PathUtility::getAbsolutePath('template/gallery.template.php'); ?>
    </div>

    <script type="text/javascript">
        onStandaloneGalleryView = true;
    </script>

    <?php include PathUtility::getAbsolutePath('template/send-mail.template.php'); ?>
    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/gallery.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <?php if ($config['gallery']['db_check_enabled']): ?>
    <script type="text/javascript" src="<?=PathUtility::getPublicPath()?>resources/js/gallery_updatecheck.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <?php endif; ?>

    <?php require_once PathUtility::getAbsolutePath('lib/services_start.php'); ?>
</body>
</html>
