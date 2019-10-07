<?php
require_once('lib/config.php');

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
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">

		<link rel="stylesheet" href="node_modules/normalize.css/normalize.css" />
		<link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css" />
		<link rel="stylesheet" href="resources/css/style.css" />
	</head>
<body data-main-image="<?=$mainimage?>">
	<div id="wrapper" style="padding: 1em 2em 2em; overflow-y: auto;">
	<?php if ($keying_possible): ?>
		<div style="width:600px; max-width: 100%; margin:0 auto;background-color:#000000;border:4px solid black;">
			<canvas id="mainCanvas" width="1500" height="1000" style="width:600px; max-width: 100%;"></canvas>
		</div>

		<div style="padding-top:10px;text-align:center;">
			<?php
				$dir = join(DIRECTORY_SEPARATOR, ['resources', 'img', 'background']) . DIRECTORY_SEPARATOR;
				$cdir = scandir($dir);
				foreach ($cdir as $key => $value) {
					if (!in_array($value, array(".","..")) && !is_dir($dir.$value)) {
						echo '<img src="'.$dir.$value.'" style="cursor:pointer;max-width:120px;border:2px solid black;margin:3px;" onclick="setBackgroundImage(this.src)">';
					}
				}
			?>
		</div>

		<div class="chroma-control-bar">
			<a class="btn btn--flex" href="javascript:saveImage()"><i class="fa fa-floppy-o"></i> <span data-l10n="save"></span></a>

			<?php if ($config['use_print']): ?>
				<a class="btn btn--flex" href="javascript:printImage()"><i class="fa fa-print"></i> <span data-l10n="print"></span></a>
			<?php endif; ?>

			<a class="btn btn--flex" href="javascript:window.history.back()"><i class="fa fa-times"></i> <span data-l10n="close"></span></a>
		</div>
	<?php else:?>
		<div style="text-align:center;padding-top:250px">
			<h1 style="color: red;" data-l10n="keyingerror"></h1>
			<a class="btn" href="./"><span data-l10n="close"></span></a>
		</div>
	<?php endif; ?>

		<div class="modal" id="print_mesg">
			<div class="modal__body" id="print_mesg_text"><span data-l10n="printing"></span></div>
		</div>
		<div class="modal" id="save_mesg">
			<div class="modal__body" id="save_mesg_text"><span data-l10n="saving"></span></div>
		</div>
	</div>
	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="node_modules/marvinj/marvinj/release/marvinj-0.8.js"></script>
	<script type="text/javascript" src="resources/js/l10n.js"></script>
	<script type="text/javascript" src="resources/lang/<?php echo $config['language']; ?>.js"></script>
	<script type="text/javascript" src="resources/js/chromakeying.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
</body>
</html>
