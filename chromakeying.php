<?php
require_once('lib/config.php');

if (empty($_GET['filename'])) {
    die('No or invalid file provided');
}

$filename = $_GET['filename'];
$keyingimage = $config['foldersRoot']['keying'] . DIRECTORY_SEPARATOR . $filename;

if (file_exists($keyingimage)) {
    // Only jpg/jpeg are supported
    $imginfo = getimagesize($keyingimage);
    $mimetype = $imginfo['mime'];
    if ($mimetype == 'image/jpg' || $mimetype == 'image/jpeg') {
        $mainimage = $keyingimage;
        $keying_possible = true;
    } else {
        $keying_possible = false;
        $mainimage = 'resources/img/bg.jpg';
    }
} else {
    $keying_possible = false;
    $mainimage = 'resources/img/bg.jpg';
}

$btnClass = 'btn btn--' . $config['ui']['button'];
$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
$fileRoot = '';
$pageTitle = $config['ui']['branding'] . ' Chromakeying';

?>
<!doctype html>
<html>
	<head>
		<?php include('template/components/mainHead.php'); ?>
		<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_chromakeying.css?v=<?php echo $config['photobooth']['version']; ?>" />
		<?php if (is_file("private/overrides.css")): ?>
		<link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>" />
		<?php endif; ?>
	</head>
<body data-main-image="<?=$mainimage?>">
	<div class="chromawrapper rotarygroup">
	<?php if ($keying_possible): ?>
		<div class="canvasWrapper <?php echo $uiShape; ?> noborder initial">
			<canvas class="<?php echo $uiShape; ?>" id="mainCanvas"></canvas>
		</div>

		<div style="padding-top:10px;text-align:center;">
			<?php
				$dir = $config['keying']['background_path'] . DIRECTORY_SEPARATOR;
				$cdir = scandir($dir);
				foreach ($cdir as $key => $value) {
					if (!in_array($value, array(".","..")) && !is_dir($dir.$value)) {
						echo '<img src="'.$dir.$value.'" class="backgroundPreview '. $uiShape .' rotaryfocus" onclick="setBackgroundImage(this.src)">';
					}
				}
			?>
		</div>

		<div class="chroma-control-bar">
			<a class="<?php echo $btnClass; ?> rotaryfocus" id="save-chroma-btn" href="#"><i class="<?php echo $config['icons']['save']; ?>"></i> <span data-i18n="save"></span></a>

			<?php if ($config['print']['from_chromakeying']): ?>
				<a class="<?php echo $btnClass; ?> rotaryfocus" id="print-btn" href="#"><i class="<?php echo $config['icons']['print']; ?>"></i> <span data-i18n="print"></span></a>
			<?php endif; ?>

			<a class="<?php echo $btnClass; ?> rotaryfocus" id="close-btn" href="#"><i class="<?php echo $config['icons']['close']; ?>"></i> <span data-i18n="close"></span></a>
		</div>
	<?php else:?>
		<div style="text-align:center;padding-top:250px">
			<h1 style="color: red;" data-i18n="keyingerror"></h1>
			<a class="<?php echo $btnClass; ?>" href="/"><span data-i18n="close"></span></a>
		</div>
	<?php endif; ?>

		<div class="modal" id="print_mesg">
			<div class="modal__body" id="print_mesg_text"><span data-i18n="printing"></span></div>
		</div>
		<div class="modal" id="modal_mesg"></div>
		<div class="modal" id="save_mesg">
			<div class="modal__body" id="save_mesg_text"><span data-i18n="saving"></span></div>
		</div>
	</div>
	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<?php if ($config['keying']['variant'] === 'marvinj'): ?>
	<script type="text/javascript" src="node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>
	<?php else:?>
	<script type="text/javascript" src="vendor/Seriously/seriously.js"></script>
	<script type="text/javascript" src="vendor/Seriously/effects/seriously.chroma.js"></script>
	<?php endif; ?>
	<script type="text/javascript" src="resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/remotebuzzer_client.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/chromakeying.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

	<?php require_once('lib/services_start.php'); ?>
</body>
</html>
