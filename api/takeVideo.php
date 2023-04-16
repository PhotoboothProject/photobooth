<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/log.php';

function takeVideo($filename) {
    global $config;
    $cmd = sprintf($config['take_video']['cmd'], $filename);
    $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

    exec($cmd, $output, $returnValue);

    if ($returnValue) {
        $ErrorData = [
            'error' => 'Take video command returned an error code',
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
            usleep(100000);
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
        // remove all files that were created - all filenames start with the videos name
        exec('rm -f ' . $filename . '*');
        $ErrorString = json_encode($ErrorData);
        logError($ErrorData);
        die($ErrorString);
    }
}

$random = Image::create_new_filename('random', '.mp4');

if (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.(mp4)$/', $_POST['file'])) {
    $file = $_POST['file'];
} else {
    $file = Image::create_new_filename($config['picture']['naming'], '.mp4');
    if ($config['database']['file'] != 'db') {
        $file = $config['database']['file'] . '_' . $file;
    }
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_random = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $random;

if (file_exists($filename_tmp)) {
    rename($filename_tmp, $filename_random);
}

takeVideo($filename_tmp);

$LogData = [
    'success' => 'video',
    'file' => $file,
    'php' => basename($_SERVER['PHP_SELF']),
];

// send imagename to frontend
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
die($LogString);
