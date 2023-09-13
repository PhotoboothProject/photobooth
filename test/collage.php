<?php
require_once '../lib/config.php';
require_once '../lib/collage.php';

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$devImg = array_diff(scandir($demoFolder), ['.', '..']);

$demoImages = [];
// Loop to select 4 random images
for ($i = 0; $i < 4; $i++) {
    // Check if there are any images left to select
    if (empty($devImg)) {
        break; // Break the loop if there are no more images
    }

    // Select a random index from the remaining images
    $randomIndex = array_rand($devImg);

    // Add the selected image to the $demoImages array
    $demoImages[] = $devImg[$randomIndex];

    // Remove the selected image from the $devImg array to avoid selecting it again
    unset($devImg[$randomIndex]);

    // Reset array keys to ensure consecutive integer keys
    $devImg = array_values($devImg);
}

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
