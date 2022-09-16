<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/log.php';
require_once '../lib/applyEffects.php';
require_once '../lib/collage.php';
require_once '../lib/collageConfig.php';

function takeVideo($filename) {
    global $config;
    $cmd = sprintf($config['take_video']['cmd'], $filename);
    $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

    exec($cmd, $output, $returnValue);

    if ($returnValue) {
        $ErrorData = [
            'error' => 'Take picture command returned an error code',
            'cmd' => $cmd,
            'returnValue' => $returnValue,
            'output' => $output,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        $ErrorString = json_encode($ErrorData);
        logError($ErrorData);
        die($ErrorString);
    }

    $i = 0;
    $processingTime = 300;
    while ($i < $processingTime) {
        if (file_exists($filename)) {
            break;
        } else {
            $i++;
            sleep(0.1);
        }
    }

    if (!file_exists($filename)) {
        $ErrorData = [
            'error' => 'File was not created',
            'cmd' => $cmd,
            'returnValue' => $returnValue,
            'output' => $output,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        $ErrorString = json_encode($ErrorData);
        if (preg_match('/^[a-z0-9_]+\.(mp4|gif)$/', $filename)) {
            // remove all files that were created - filenames all start with the videos name
            exec('rm -f ' . $filename . '*');
        }
        logError($ErrorData);
        die($ErrorString);
    }

    $moveFiles[] = $filename;
    $images = [];
    for ($i = 1; $i < 99; $i++) {
        $imageFilename = sprintf('%s-%02d.jpg', $filename, $i);
        if (file_exists($imageFilename)) {
            $images[] = $imageFilename;
        } else {
            break;
        }
    }
    // If there are 4 images create a cuttable collage (more flexibility to come one day)
    if ($config['video']['collage'] && count($images) === 4) {
        $collageFilename = sprintf('%s-collage.jpg', $filename);
        $collageConfig = new CollageConfig();
        $collageConfig->collageLayout = '2x4-3';
        $collageConfig->collageTakeFrame = 'off';
        $collageConfig->collagePlaceholder = false;
        if (!createCollage($images, $collageFilename, $config['filters']['defaults'], $collageConfig)) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not create collage';
            logErrorAndDie($errormsg);
        }
    }

    $dataFolder = $config['folders']['data'] . DIRECTORY_SEPARATOR;
    foreach ($moveFiles as $file) {
        $newFile = $dataFolder . basename($file);
        rename($file, $newFile);
        if ($config['database']['enabled']) {
            appendImageToDB($newFile);
        }
    }
    // todo show video as result? but print collage? show qr for this / specific mode only?
    // todo show some kind of result screen. move images to folder, adding to gallery completely failed (maybe because of the folder)
}

$random = md5(time()) . '.mp4';

if (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.(mp4|gif)$/', $_POST['file'])) {
    $name = $_POST['file'];
} elseif ($config['picture']['naming'] === 'numbered') {
    if ($config['database']['enabled']) {
        $images = getImagesFromDB();
    } else {
        $images = getImagesFromDirectory($config['foldersAbs']['images']);
    }
    $img_number = count($images);
    $files = str_pad(++$img_number, 4, '0', STR_PAD_LEFT);
    $name = $files . '.mp4';
} elseif ($config['picture']['naming'] === 'dateformatted') {
    $name = date('Ymd_His') . '.mp4';
} else {
    $name = $random;
}

if ($config['database']['file'] === 'db' || (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.(mp4|gif)$/', $_POST['file']))) {
    $file = $name;
} else {
    $file = $config['database']['file'] . '_' . $name;
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_random = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $random;

if (file_exists($filename_tmp)) {
    rename($filename_tmp, $filename_random);
}

takeVideo($filename_tmp);

$LogData = [
    'success' => 'image',
    'file' => $file,
    'php' => basename($_SERVER['PHP_SELF']),
];

// send imagename to frontend
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
die($LogString);
