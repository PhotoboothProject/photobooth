<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';

$pageTitle = $config['ui']['branding'] . ' Preview-Test';
$mainStyle = 'test_preview.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include($fileRoot . 'template/components/main.head.php');
?>

<body>
    <img id="picture--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['picture']['htmlframe']; ?>" alt="pictureFrame" />
    <img id="collage--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['collage']['htmlframe']; ?>" alt="collageFrame" />
    <video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" autoplay playsinline></video>

    <div id="wrapper">
        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

        <div id="no_preview">
            <span data-i18n="no_preview"></span>
        </div>

		<div class="buttonbar">
			<a href="#" class="<?php echo $btnClass; ?> startPreview"><span data-i18n="startPreview"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> stopPreview"><span data-i18n="stopPreview"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> showPictureFrame"><span data-i18n="showPictureFrame"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> showCollageFrame"><span data-i18n="showCollageFrame"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> hideFrame"><span data-i18n="hideFrame"></span></a>
        </div>
    </div>

    <?php include($fileRoot . 'template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=$fileRoot?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
