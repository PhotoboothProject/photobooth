<?php
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/polaroid.php';
require_once '../lib/resize.php';
require_once '../lib/applyText.php';
require_once '../lib/applyFrame.php';
require_once '../lib/applyEffects.php';

$style = isset($_GET['style']) ? $_GET['style'] : null;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'plain';
$chromaEnabled = $config['keying']['enabled'] || $style === 'chroma';

$demoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/img/demo';
$demoFolder = realpath($demoPath);
$devImg = array_diff(scandir($demoFolder), ['.', '..']);
$demoImage = $devImg[array_rand($devImg)];

$name = date('Ymd_His') . '.jpg';
$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $name;
copy($demoFolder . DIRECTORY_SEPARATOR . $demoImage, $filename_tmp);

$out_file = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'result_' . $name;
$out_fileRoot = $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'result_' . $name;
$out_thumb_file = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'result_thumb_' . $name;
$out_thumb_fileRoot = $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'result_thumb_' . $name;
$out_keying_file = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'result_keying_' . $name;
$out_keying_fileRoot = $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'result_keying_' . $name;

$picture_frame = $config['picture']['frame'];

$imageResource = imagecreatefromjpeg($filename_tmp);
list($imageResource, $imageModified) = editSingleImage($config, $imageResource, $filter, true, $picture_frame, false);
if ($chromaEnabled) {
    $chroma_size = substr($config['keying']['size'], 0, -2);
    $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
    imagejpeg($chromaCopyResource, $out_keying_file, $config['jpeg_quality']['chroma']);
    imagedestroy($chromaCopyResource);
}

$configText = $config['textonpicture'];
list($imageResource, $imageModified) = addTextToImage($configText, $imageResource, $imageModified, false);

$thumb_size = substr($config['picture']['thumb_size'], 0, -2);
$thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

imagejpeg($thumbResource, $out_thumb_file, $config['jpeg_quality']['thumb']);
imagedestroy($thumbResource);

compressImage($config, $imageModified, $imageResource, $filename_tmp, $out_file);

unlink($filename_tmp);
?>
<html>
<body style="width: 80%; height:80%; background-color: <?php echo $config['colors']['primary']; ?>">
<div><p>Image</p></div>
<img style="max-width: 100%;  max-height: 100%; " src="../<?php echo $out_fileRoot; ?>">
<div><p>Thumbnail:</p></div>
<img style="max-width: 100%;  max-height: 100%; " src="../<?php echo $out_thumb_fileRoot; ?>">
<?php if ($chromaEnabled) { ?>
    <div><p>Chroma:</p></div>
    <img style="max-width: 100%;  max-height: 100%; " src="../<?php echo $out_keying_fileRoot; ?>">
<?php } ?>
</body>
</html>
