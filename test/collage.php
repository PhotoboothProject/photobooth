<?php
require_once '../lib/config.php';
require_once '../lib/collage.php';

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$demoImages = [
    'adi-goldstein-Hli3R6LKibo-unsplash.jpg',
    'alasdair-elmes-ULHxWq8reao-unsplash.jpg',
    'elena-de-soto-w423NnHFjFg-unsplash.jpg',
    'matty-adame-nLUb9GThIcg-unsplash.jpg',
];

$name = date('Ymd_His') . '.jpg';
$collageSrcImagePaths = [];
for ($i = 0; $i < $config['collage']['limit']; $i++) {
    $image = $demoImages[$i];
    $path = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $i . '_' . $name;
    copy($demoFolder . DIRECTORY_SEPARATOR . $image, $path);
    $collageSrcImagePaths[] = $path;
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
