<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/polaroid.php';
require_once '../lib/resize.php';
require_once '../lib/collage.php';
require_once '../lib/applyText.php';

if (!extension_loaded('gd')) {
    die(
        json_encode([
            'error' => 'GD library not loaded! Please enable GD!',
        ])
    );
}

if (empty($_POST['file'])) {
    die(
        json_encode([
            'error' => 'No file provided',
        ])
    );
}

$file = $_POST['file'];

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$picture_frame = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $config['picture']['frame']);
$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);
$chroma_size = substr($config['keying']['size'], 0, -2);

// text on picture variables
$fontpath = $config['textonpicture']['font'];
$fontcolor = $config['textonpicture']['font_color'];
$fontsize = $config['textonpicture']['font_size'];
$fontlocx = $config['textonpicture']['locationx'];
$fontlocy = $config['textonpicture']['locationy'];
$linespacing = $config['textonpicture']['linespace'];
$fontrot = $config['textonpicture']['rotation'];
$line1text = $config['textonpicture']['line1'];
$line2text = $config['textonpicture']['line2'];
$line3text = $config['textonpicture']['line3'];

if (!isset($_POST['style'])) {
    die(
        json_encode([
            'error' => 'No style provided',
        ])
    );
}

if ($_POST['style'] === 'collage') {
    if ($config['collage']['take_frame'] !== 'off') {
        if (is_dir(COLLAGE_FRAME)) {
            die(
                json_encode([
                    'error' => 'Frame not set! ' . COLLAGE_FRAME . ' is a path but needs to be a png!',
                ])
            );
        }

        if (!file_exists(COLLAGE_FRAME)) {
            die(
                json_encode([
                    'error' => 'Frame ' . COLLAGE_FRAME . ' does not exist!',
                ])
            );
        }
    }

    if ($config['textoncollage']['enabled']) {
        if (is_dir(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . TEXTONCOLLAGE_FONT))) {
            die(
                json_encode([
                    'error' => 'Font not set! ' . TEXTONCOLLAGE_FONT . ' is a path but needs to be a ttf!',
                ])
            );
        }

        if (!file_exists(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . TEXTONCOLLAGE_FONT))) {
            die(
                json_encode([
                    'error' => 'Font ' . TEXTONCOLLAGE_FONT . ' does not exist!',
                ])
            );
        }
    }

    $collageBasename = substr($filename_tmp, 0, -4);
    $collageSrcImagePaths = [];

    for ($i = 0; $i < $config['collage']['limit']; $i++) {
        $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
    }

    if (!createCollage($collageSrcImagePaths, $filename_tmp)) {
        die(
            json_encode([
                'error' => 'Could not create collage',
            ])
        );
    }

    if (!$config['picture']['keep_original']) {
        foreach ($collageSrcImagePaths as $tmp) {
            unlink($tmp);
        }
    }
}

if (!file_exists($filename_tmp)) {
    die(
        json_encode([
            'error' => 'File does not exist',
        ])
    );
}

if ($config['picture']['take_frame']) {
    if (is_dir($picture_frame)) {
        die(
            json_encode([
                'error' => 'Frame not set! ' . $picture_frame . ' is a path but needs to be a png!',
            ])
        );
    }

    if (!file_exists($picture_frame)) {
        die(
            json_encode([
                'error' => 'Frame ' . $picture_frame . ' does not exist!',
            ])
        );
    }
}

if ($config['textonpicture']['enabled']) {
    if (is_dir(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $fontpath))) {
        die(
            json_encode([
                'error' => 'Font not set! ' . $fontpath . ' is a path but needs to be a ttf!',
            ])
        );
    }

    if (!file_exists(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $fontpath))) {
        die(
            json_encode([
                'error' => 'Font ' . $fontpath . ' does not exist!',
            ])
        );
    }
}

// Only jpg/jpeg are supported
$imginfo = getimagesize($filename_tmp);
$mimetype = $imginfo['mime'];
if ($mimetype != 'image/jpg' && $mimetype != 'image/jpeg') {
    die(
        json_encode([
            'error' => 'The source file type ' . $mimetype . ' is not supported',
        ])
    );
}

$imageResource = imagecreatefromjpeg($filename_tmp);
$imageModified = false;

if (!$imageResource) {
    die(
        json_encode([
            'error' => 'Could not read jpeg file. Are you taking raws?',
        ])
    );
}

if (!isset($_POST['filter'])) {
    die(
        json_encode([
            'error' => 'No filter provided',
        ])
    );
}

$image_filter = false;

if (!empty($_POST['filter']) && $_POST['filter'] !== 'plain') {
    $image_filter = $_POST['filter'];
}

if ($config['picture']['flip'] !== 'off') {
    if ($config['picture']['flip'] === 'horizontal') {
        imageflip($imageResource, IMG_FLIP_HORIZONTAL);
    } elseif ($config['picture']['flip'] === 'vertical') {
        imageflip($imageResource, IMG_FLIP_VERTICAL);
    } elseif ($config['picture']['flip'] === 'both') {
        imageflip($imageResource, IMG_FLIP_BOTH);
    }
    $imageModified = true;
}
// apply filter
if ($image_filter) {
    applyFilter($image_filter, $imageResource);
    $imageModified = true;
}

if ($config['picture']['rotation'] !== '0') {
    $rotatedImg = imagerotate($imageResource, $config['picture']['rotation'], 0);
    $imageResource = $rotatedImg;
    $imageModified = true;
}

if ($config['picture']['polaroid_effect']) {
    $polaroid_rotation = $config['picture']['polaroid_rotation'];
    $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
    $imageModified = true;
}

if ($config['picture']['take_frame'] && $_POST['style'] !== 'collage') {
    $frame = imagecreatefrompng($picture_frame);
    $frame = resizePngImage($frame, imagesx($imageResource), imagesy($imageResource));
    $x = imagesx($imageResource) / 2 - imagesx($frame) / 2;
    $y = imagesy($imageResource) / 2 - imagesy($frame) / 2;
    imagecopy($imageResource, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
    $imageModified = true;
}

if ($config['keying']['enabled'] || $_POST['style'] === 'chroma') {
    $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
    imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality']['chroma']);
    imagedestroy($chromaCopyResource);
}

if ($config['textonpicture']['enabled'] && $_POST['style'] !== 'collage') {
    imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
    imagedestroy($imageResource);
    ApplyText($filename_photo, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolor, $fontpath, $line1text, $line2text, $line3text, $linespacing);
    $imageModified = true;
    $imageResource = imagecreatefromjpeg($filename_photo);
}

// image scale, create thumbnail
$thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb']);
imagedestroy($thumbResource);

if ($imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
    imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
    // preserve jpeg meta data
    if ($config['picture']['preserve_exif_data'] && $config['exiftool']['cmd']) {
        $cmd = sprintf($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
        exec($cmd, $output, $returnValue);
        if ($returnValue) {
            die(
                json_encode([
                    'error' => 'exiftool returned with an error code',
                    'cmd' => $cmd,
                    'returnValue' => $returnValue,
                    'output' => $output,
                ])
            );
        }
    }
} else {
    copy($filename_tmp, $filename_photo);
}

if (!$config['picture']['keep_original']) {
    unlink($filename_tmp);
}

imagedestroy($imageResource);

// insert into database
if ($config['database']['enabled']) {
    if ($_POST['style'] !== 'chroma' || ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === true)) {
        appendImageToDB($file);
    }
}

// Change permissions
chmod($filename_photo, octdec($picture_permissions));

if ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === false) {
    unlink($filename_photo);
    unlink($filename_thumb);
}

echo json_encode([
    'file' => $file,
]);
