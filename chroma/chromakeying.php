<?php
$fileRoot = '../';

require_once($fileRoot . 'lib/config.php');

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
        $mainimage = . $fileRoot . 'resources/img/bg.jpg';
    }
} else {
    $keying_possible = false;
    $mainimage = $fileRoot . 'resources/img/bg.jpg';
}

$pageTitle = $config['ui']['branding'] . ' Chromakeying';
$mainStyle = $config['ui']['style'] . '_chromakeying.css';
$photoswipe = false;
$remoteBuzzer = true;
$chromaKeying = true;
$GALLERY_FOOTER = true;

include($fileRoot . 'template/components/main.head.php');
?>

<body data-main-image="<?=$mainimage?>">
	<div class="chromawrapper rotarygroup">
	<?php if ($keying_possible): ?>
		<div class="canvasWrapper <?php echo $uiShape; ?> noborder initial">
			<canvas class="<?php echo $uiShape; ?>" id="mainCanvas"></canvas>
		</div>

		<div style="padding-top:10px;text-align:center;">
			<?php
				$dir = '..' . DIRECTORY_SEPARATOR . $config['keying']['background_path'] . DIRECTORY_SEPARATOR;
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

	<?php include($fileRoot . 'template/modal.template.php'); ?>

	</div>

        <?php
	    include($fileRoot . 'template/components/main.footer.php');
	    require_once($fileRoot . 'lib/services_start.php');
	?>
</body>
</html>
