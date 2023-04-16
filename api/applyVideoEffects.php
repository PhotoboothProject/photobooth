<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/applyEffects.php';
require_once '../lib/collageConfig.php';
require_once '../lib/collage.php';

$file = $_POST['file'];
$tmpFolder = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR;
$imageFolder = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR;
$thumbsFolder = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR;
$filenameTmp = $tmpFolder . $file;
$filenameOutput = $imageFolder . $file;
$filenameThumb = $thumbsFolder . $file;

if (!file_exists($filenameTmp)) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $filenameTmp . ' does not exist';
    logErrorAndDie($errormsg);
}

$frames = [];
for ($i = 1; $i < 99; $i++) {
    $frameFilename = sprintf('%s-%02d.jpg', $filenameTmp, $i);
    if (file_exists($frameFilename)) {
        $frames[] = $frameFilename;
    } else {
        break;
    }
}

// If the video command created 4 images, create a cuttable collage (more flexibility to maybe come one day)
$collageFilename = '';
$images = [];
if ($config['video']['collage'] && count($frames) === 4) {
    $collageFilename = sprintf('%s-collage.jpg', $file);
    $collageConfig = new CollageConfig();
    $collageConfig->collageLayout = '2x4-3';
    $collageConfig->collageTakeFrame = 'off';
    $collageConfig->collagePlaceholder = false;
    if (!createCollage($frames, $collageFilename, $config['filters']['defaults'], $collageConfig)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not create collage';
        logErrorAndDie($errormsg);
    }
    $images[] = $collageFilename;
}

if (!$config['video']['collage_keep_images']) {
    foreach ($frames as $frame) {
        unlink($frame);
    }
} else {
    $images = array_merge($images, $frames);
}

foreach ($images as $image) {
    $imageResource = imagecreatefromjpeg($image);
    $thumbSize = substr($config['picture']['thumb_size'], 0, -2);
    $thumbResource = resizeImage($imageResource, $thumbSize, $thumbSize);
    imagejpeg($thumbResource, $thumbsFolder . basename($image), $config['jpeg_quality']['thumb']);
    imagedestroy($thumbResource);
    $newFile = $imageFolder . basename($image);
    compressImage($config, false, $imageResource, $image, $newFile);
    if (!$config['picture']['keep_original']) {
        unlink($image);
    }
    imagedestroy($imageResource);
    if ($config['database']['enabled']) {
        appendImageToDB(basename($newFile));
    }
    $picture_permissions = $config['picture']['permissions'];
    chmod($newFile, octdec($picture_permissions));
}

if ($config['video']['collage_only']) {
    if ($collageFilename === '' || !file_exists($imageFolder . $collageFilename)) {
        $ErrorData = [
            'error' => 'Configured to save only Collage but collage file does not exist: ' . $collageFilename,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        logErrorAndDie($ErrorData);
    }
    if (!$config['picture']['keep_original']) {
        unlink($filenameTmp);
    }
    $file = $collageFilename;
} else {
    $cfilter = [];
    $additionalParams = '';
    if ($config['video']['effects'] !== 'None') {
        if ($config['video']['effects'] === 'boomerang') {
            // get second to last frame to prevent frame duplication
            $frames = shell_exec("ffprobe -v error -select_streams v:0 -count_packets \
    -show_entries stream=nb_read_packets -of csv=p=0 $filenameTmp");
            $secondToLastFrame = intval($frames) - 1;
            logError($secondToLastFrame);

            $cfilter[] = "[0]trim=start_frame=1:end_frame=$secondToLastFrame,setpts=PTS-STARTPTS,reverse[r];[0][r]concat=n=2:v=1:a=0";
        }
    }

    if ($config['video']['gif']) {
        $cfilter[] = 'split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse';
        $additionalParams .= ' -loop 0 ';
        $info = pathinfo($filenameOutput);
        $file = $info['filename'] . '.gif';
        $filenameOutput = $imageFolder . $file;
    } else {
        $additionalParams = ' -vcodec libx264 -pix_fmt yuv420p';
    }

    $filterComplex = '';
    if (count($cfilter) > 0) {
        $filterComplex = '-filter_complex "' . implode(',', $cfilter) . '"';
    }

    $cmd = "ffmpeg -i $filenameTmp $filterComplex $additionalParams $filenameOutput";
    exec($cmd, $output, $returnValue);

    if (!$config['picture']['keep_original']) {
        unlink($filenameTmp);
    }

    if ($returnValue != 0) {
        $ErrorData = [
            'error' => 'Take picture command returned an error code',
            'cmd' => $cmd,
            'returnValue' => $returnValue,
            'output' => json_encode($output),
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        logErrorAndDie($ErrorData);
    }

    /* TODO gallery doesn't support videos atm
    // insert into database
    if ($config['database']['enabled']) {
        appendImageToDB($file);
    }*/

    // Change permissions
    $picture_permissions = $config['picture']['permissions'];
    chmod($filenameOutput, octdec($picture_permissions));
}

$images = [];
foreach (glob("$filenameOutput*") as $filename) {
    $images[] = basename($filename);
}

$LogData = [
    'file' => $file,
    'images' => $images,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
echo $LogString;
