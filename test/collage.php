<?php
require_once '../lib/config.php';
require_once '../lib/collage.php';

if ($config['collage']['take_frame'] !== 'off') {
    if (is_dir(COLLAGE_FRAME)) {
        die('Frame not set! ' . COLLAGE_FRAME . ' is a path but needs to be a png!');
    }

    if (!file_exists(COLLAGE_FRAME)) {
        die('Frame ' . COLLAGE_FRAME . ' does not exist!');
    }
}

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$demoImages = [
    'adi-goldstein-Hli3R6LKibo-unsplash.jpg',
    'alasdair-elmes-ULHxWq8reao-unsplash.jpg',
    'elena-de-soto-w423NnHFjFg-unsplash.jpg',
    'matty-adame-nLUb9GThIcg-unsplash.jpg',
];

$name = date('Ymd_His') . '.jpg';
$i = 0;
foreach ($demoImages as $image) {
    if ($i < $config['collage']['limit']) {
        copy($demoFolder . DIRECTORY_SEPARATOR . $image, $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $i++ . '_' . $name);
    }
}
$collageSrcImagePaths = [];
for ($j = 0; $j < $config['collage']['limit']; $j++) {
    $collageSrcImagePaths[] = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $j . '_' . $name;
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'result_' . $name;
$out_file = $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'result_' . $name;

if (createCollage($collageSrcImagePaths, $filename_tmp, $config['filters']['defaults'])) {
    for ($k = 0; $k < $config['collage']['limit']; $k++) {
        unlink($collageSrcImagePaths[$k]);
    } ?>
		<html>
			<body style="width: 80%; height:80%; background-color: <?php echo $config['colors']['primary']; ?>">
				<img style="max-width: 100%;  max-height: 100%; " src="../<?php echo $out_file; ?>">
			</body>
		</html>
<?php
}
