<?php
$my_config = 'my.config.inc.php';
if (file_exists($my_config)) {
	require_once('my.config.inc.php');
} else {
	require_once('config.inc.php');
}

$location = base64_decode($_GET['location']);
$filename = $_GET['filename'];
$keyingimage = $config['folders']['keying'] . DIRECTORY_SEPARATOR . $filename;
if (file_exists($keyingimage)) {
	$mainimage = $keyingimage;
	$keying_possible = true;
} else {
	$keying_possible = false;
	$mainimage = 'resources/img/bg.jpg';
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
<link rel="stylesheet" href="resources/css/normalize.css" />
<link rel="stylesheet" href="resources/css/default-skin/default-skin.css" />
<link rel="stylesheet" href="resources/css/font-awesome.min.css" />
<link rel="stylesheet" href="resources/css/style.css" />
	<script type="text/javascript">
		var isdev = <?php echo ($config['dev']) ? 'true' : 'false'; ?>;
		var theme = <?php echo $config['bluegray_theme'] ? "'bluegray'" : "'default'"; ?>;
	</script>
</head>
<body>
<div id="wrapper" style="padding: 1em 2em 2em; overflow-y: auto;">
<?php if($keying_possible){ ?>
	<div style="width:600px; max-width: 100%; margin:0 auto;background-color:#000000;border:4px solid black;">
		<canvas id="mainCanvas" width="1500" height="1000" style="width:600px; max-width: 100%;"></canvas>
	</div>

	<div style="padding-top:10px;text-align:center;">
		<?php
			$cdir = scandir("chroma_keying/background");
			foreach ($cdir as $key => $value) {
				if (!in_array($value, array(".","..")) && !is_dir($dir.DIRECTORY_SEPARATOR.$value)) {
					echo '<img src="chroma_keying/background/'.$value.'" style="cursor:pointer;max-width:120px;border:2px solid black;margin:3px;" onclick="setBackgroundImage(this.src)">';
				}
			}
		?>
	</div>

	<div class="chroma-control-bar">
		<button class="btn btn--flex" onclick="saveImage()"><i class="fa fa-floppy-o"></i> <span data-l10n="save"></span></button>
		<?php if($config['use_print']): ?>
			<button class="btn btn--flex" onclick="printImage()"><i class="fa fa-print"></i> <span data-l10n="print"></span></button>
		<?php endif; ?>
		<button class="btn btn--flex" onclick="navigateToMain()"><i class="fa fa-times"></i> <span data-l10n="close"></span></button>
	</div>
<?php } else { ?>
	<div style="text-align:center;padding-top:250px">
		<h1 style="color: red;" data-l10n="keyingerror"></h1>
		<button class="btn" onclick="navigateToMain()"><span data-l10n="close"></span></button>
	</div>
<?php } ?>
	<div class="modal" id="print_mesg">
		<div class="modal__body" id="print_mesg_text"><span data-l10n="printing"></span></div>
	</div>
	<div class="modal" id="save_mesg">
		<div class="modal__body" id="save_mesg_text"><span data-l10n="saving"></span></div>
	</div>
</div>
	<script type="text/javascript" src="resources/js/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="resources/js/marvinj-0.8.js"></script>
	<script type="text/javascript" src="resources/js/l10n.js"></script>
	<script type="text/javascript" src="lang/<?php echo $config['language']; ?>.js"></script>
	<script type="text/javascript" src="resources/js/chromakeying.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script>
	$( document ).ready(function() {
		setTimeout(function(){
			setMainImage('<?php echo $mainimage ?>');
		}, 100);
	});

	function navigateToMain() {
		$(location).attr('href','<?php echo $location?>');
	}
	</script>
</body>
</html>
