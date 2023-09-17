<?php

$fileRoot = '../';
require_once $fileRoot . 'lib/boot.php';

$pageTitle = $config['ui']['branding'] . ' Preview-Test';
$mainStyle = 'test_preview.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include($fileRoot . 'template/components/main.head.php');
?>

<body>
    <?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])): ?>
    <img id="picture--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['picture']['htmlframe']; ?>" alt="pictureFrame" />
    <?php endif; ?>
    <?php if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
    <img id="collage--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['collage']['htmlframe']; ?>" alt="collageFrame" />
    <?php endif; ?>
    <video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" autoplay playsinline></video>

    <div id="wrapper">
        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

        <div id="no_preview">
            <span data-i18n="no_preview"></span>
        </div>

		<div class="buttonbar">
			<a href="#" class="<?php echo $btnClass; ?> startPreview"><span data-i18n="startPreview"></span></a>
			<a href="#" class="<?php echo $btnClass; ?> stopPreview"><span data-i18n="stopPreview"></span></a>
			<?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])): ?>
			<a href="#" class="<?php echo $btnClass; ?> showPictureFrame"><span data-i18n="showPictureFrame"></span></a>
			<?php endif; ?>
			<?php if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
			<a href="#" class="<?php echo $btnClass; ?> showCollageFrame"><span data-i18n="showCollageFrame"></span></a>
			<?php endif; ?>
			<?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe']) || $config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
			<a href="#" class="<?php echo $btnClass; ?> hideFrame"><span data-i18n="hideFrame"></span></a>
			<?php endif; ?>
			<a href="<?php echo $fileRoot . 'test'; ?>" class="<?php echo $btnClass; ?> backBtn"><span data-i18n="back"></span></a>
        </div>
    </div>

    <?php include($fileRoot . 'template/components/main.footer.php'); ?>

    <script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="<?=$fileRoot?>resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
