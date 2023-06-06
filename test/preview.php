<?php
require_once '../lib/config.php';

$btnClass = 'btn btn--' . $config['ui']['button'];
$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];

$fileRoot = '../';
$pageTitle = $config['ui']['branding'] . ' Preview-Test';
?>

<!DOCTYPE html>
<html>

<head>
	<?php include('../template/components/mainHead.php'); ?>

    <link rel="stylesheet" href="../resources/css/test_preview.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php if (is_file('../private/overrides.css')): ?>
        <link rel="stylesheet" href="../private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
</head>

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

<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="../resources/js/test_preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
