<?php
$location = base64_decode($_GET['location']);
$filename = $_GET['filename'];
?>
<!doctype html>
<html>
<head>
<script type="text/javascript" src="lib/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="lib/marvinj-0.8.js"></script>
<script type="text/javascript" src="lib/custom.js"></script>
<script>
$( document ).ready(function() {
	setTimeout(function(){
		setMainImage('../images<?php echo $filename?>');
	}, 100);
});

function navigateToMain() {
	$(location).attr('href','<?php echo $location?>');
}
</script>
</head>
<body style="background: url(../resources/img/bg.jpg) center center no-repeat;">
<br>
<div style="width:90%; margin:0 auto;">
	<div style="width:600px;height:400px; margin:0 auto;background-color:#000000;border:4px solid black;">
		<canvas id="mainCanvas" width="1500" height="1000" style="width:600px;height:400px;"></canvas>
	</div>

	<div style="padding-top:10px;text-align:center;">
		<?php
			$cdir = scandir("background");
			foreach ($cdir as $key => $value) {
				if (!in_array($value, array(".","..")) && !is_dir($dir.DIRECTORY_SEPARATOR.$value)) {
					echo '<img src="background/'.$value.'" style="cursor:pointer;max-width:120px;border:2px solid black;margin:3px;" onclick="setBackgroundImage(this.src)">';
				}
			}
		?>
	</div>

	<div style="padding-top:10px;text-align:center;">
		<button style="width:150px;height:50px;font-size:20px;cursor:pointer;" onclick="saveImage()">Speichern</button>
		<button style="width:150px;height:50px;font-size:20px;cursor:pointer;" onclick="printImage()">Drucken</button>
		<button style="width:150px;height:50px;font-size:20px;cursor:pointer;" onclick="navigateToMain()">Schlie&szlig;en</button>
	</div>
</div>

</body>
</html>