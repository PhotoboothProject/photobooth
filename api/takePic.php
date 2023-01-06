<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/log.php';

function takePicture($filename) {
    global $config;

    if ($config['dev']['demo_images']) {
        $demoFolder = __DIR__ . '/../resources/img/demo/';
        $devImg = array_diff(scandir($demoFolder), ['.', '..']);
        copy($demoFolder . $devImg[array_rand($devImg)], $filename);
    } elseif ($config['preview']['mode'] === 'device_cam' && $config['preview']['camTakesPic']) {
        $data = $_POST['canvasimg'];
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);

        file_put_contents($filename, $data);

        if ($config['preview']['flip'] != 'off') {
            $im = imagecreatefromjpeg($filename);
            switch ($config['preview']['flip']) {
                case 'flip-horizontal':
                    imageflip($im, IMG_FLIP_HORIZONTAL);
                    break;
                case 'flip-vertical':
                    imageflip($im, IMG_FLIP_VERTICAL);
                    break;
                default:
                    break;
            }
            imagejpeg($im, $filename);
            imagedestroy($im);
        }
    } else {
        //gphoto must be executed in a dir with write permission for other commands we stay in the api dir
        if (substr($config['take_picture']['cmd'], 0, strlen('gphoto')) === 'gphoto') {
            chdir(dirname($filename));
        }
        $cmd = sprintf($config['take_picture']['cmd'], $filename);
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
        } elseif (!file_exists($filename)) {
            $ErrorData = [
                'error' => 'File was not created',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
                'php' => basename($_SERVER['PHP_SELF']),
            ];
            $ErrorString = json_encode($ErrorData);
            logError($ErrorData);
            die($ErrorString);
        }
    }
}

$random = Image::create_new_filename('random');

if (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file'])) {
    $file = $_POST['file'];
} else {
    $file = Image::create_new_filename($config['picture']['naming']);
    if ($config['database']['file'] != 'db') {
        $file = $config['database']['file'] . '_' . $file;
    }
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
$filename_random = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $random;

if (file_exists($filename_tmp)) {
    rename($filename_tmp, $filename_random);
}

if (!isset($_POST['style'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No style provided';
    logErrorAndDie($errormsg);
}

switch ($_POST['style']) {
    case 'photo':
        takePicture($filename_tmp);

        $LogData = [
            'success' => 'image',
            'file' => $file,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        break;
    case 'collage':
        if (!is_numeric($_POST['collageNumber'])) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': No or invalid collage number provided';
            logErrorAndDie($errormsg);
        }

        $number = $_POST['collageNumber'] + 0;

        if ($number > $config['collage']['limit']) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Collage consists only of ' . $config['collage']['limit'] . ' pictures';
            logErrorAndDie($errormsg);
        }

        $basecollage = substr($file, 0, -4);
        $collage_name = $basecollage . '-' . $number . '.jpg';

        $basename = substr($filename_tmp, 0, -4);
        $filename = $basename . '-' . $number . '.jpg';

        takePicture($filename);

        $LogData = [
            'success' => 'collage',
            'file' => $file,
            'collage_file' => $collage_name,
            'current' => $number,
            'limit' => $config['collage']['limit'],
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        break;
    case 'chroma':
        takePicture($filename_tmp);

        $LogData = [
            'success' => 'chroma',
            'file' => $file,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        break;
    default:
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Invalid photo style provided';
        logErrorAndDie($errormsg);
        break;
}

// send imagename to frontend
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
die($LogString);
