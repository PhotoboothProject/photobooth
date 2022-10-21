<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/log.php';

$file = $_POST['file'];
$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;

if (!file_exists($filename_tmp)) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $filename_tmp . ' does not exist';
    logErrorAndDie($errormsg);
}

$cfilter = [];
$additional_params = '';
if ($config['video']['effects'] !== 'None') {
    if ($config['video']['effects'] === 'boomerang') {
        // get second to last frame to prevent frame duplication
        $frames = shell_exec("ffprobe -v error -select_streams v:0 -count_packets \
    -show_entries stream=nb_read_packets -of csv=p=0 $filename_tmp");
        $frame_second_to_last = intval($frames) - 1;
        logError($frame_second_to_last);

        $cfilter[] = "[0]trim=start_frame=1:end_frame=$frame_second_to_last,setpts=PTS-STARTPTS,reverse[r];[0][r]concat=n=2:v=1:a=0";
    }
}

if ($config['video']['gif']) {
    $cfilter[] = ',split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse';
    $additional_params .= '-loop 0';
    $info = pathinfo($filename_photo);
    $filename_photo = $info['filename'] . '.gif';
}

$filter_complex = '';
if (count($cfilter) > 0) {
    $filter_complex = '-filter_complex "' . implode(',', $cfilter) . '"';
}

$cmd = "ffmpeg -i $filename_tmp $filter_complex $additional_params $filename_photo";
exec($cmd, $output, $returnValue);

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

// insert into database
if ($config['database']['enabled']) {
    appendImageToDB($filename_photo);
}

// Change permissions
$picture_permissions = $config['picture']['permissions'];
chmod($filename_photo, octdec($picture_permissions));

$images = [$file];
foreach (glob("$filename_photo*") as $filename) {
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
