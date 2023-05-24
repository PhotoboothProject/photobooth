<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/capture.php';
require_once '../lib/nextcloud.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

try {
    if (!isset($_POST['style'])) {
        throw new Exception('No style provided');
    }

    if (!empty($_POST['file']) && (preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file']) || preg_match('/^[a-z0-9_]+\.(mp4)$/', $_POST['file']))) {
        $file = $_POST['file'];
    } else {
        $file = $_POST['style'] === 'video' ? Image::createNewFilename($config['picture']['naming'], '.mp4') : Image::createNewFilename($config['picture']['naming']);
        if ($config['database']['file'] != 'db') {
            $file = $config['database']['file'] . '_' . $file;
        }
    }

    $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;
    if (file_exists($filename_tmp)) {
        $random = $_POST['style'] === 'video' ? Image::createNewFilename('random', '.mp4') : Image::createNewFilename('random');
        $filename_random = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $random;
        rename($filename_tmp, $filename_random);
    }

    $captureHandler = new PhotoboothCapture($Logger);
    $captureHandler->debugLevel = $config['dev']['loglevel'];
    $captureHandler->fileName = $file;
    $captureHandler->tmpFile = $filename_tmp;

    switch ($_POST['style']) {
        case 'photo':
            $captureHandler->style = 'image';
            break;
        case 'collage':
            if (!is_numeric($_POST['collageNumber'])) {
                throw new Exception('No or invalid collage number provided.');
            }

            $number = $_POST['collageNumber'] + 0;

            if ($number > $config['collage']['limit']) {
                throw new Exception('Collage consists only of ' . $config['collage']['limit'] . ' pictures');
            }

            $captureHandler->collageSubFile = substr($file, 0, -4) . '-' . $number . '.jpg';
            $captureHandler->tmpFile = substr($filename_tmp, 0, -4) . '-' . $number . '.jpg';
            $captureHandler->style = 'collage';
            $captureHandler->collageNumber = $number;
            $captureHandler->collageLimit = $config['collage']['limit'];
            break;
        case 'chroma':
            $captureHandler->style = 'chroma';
            break;
        case 'custom':
            $captureHandler->style = 'image';
            break;
        case 'video':
            $captureHandler->style = 'video';
            break;
        default:
            throw new Exception('Invalid style provided.');
            break;
    }

    if ($_POST['style'] === 'video') {
        $captureHandler->captureCmd = $config['take_video']['cmd'];
        $captureHandler->captureWithCmd();
    } elseif ($config['dev']['demo_images']) {
        $captureHandler->captureDemo();
    } elseif ($config['preview']['mode'] === 'device_cam' && $config['preview']['camTakesPic']) {
        $captureHandler->flipImage = $config['preview']['flip'];
        $captureHandler->captureCanvas($_POST['canvasimg']);
    } else {
        if ($_POST['style'] === 'custom') {
            $captureHandler->captureCmd = $config['take_custom']['cmd'];
        } else {
            $captureHandler->captureCmd = $config['take_picture']['cmd'];
        }
        $captureHandler->captureWithCmd();
    }

    // send image to frontend
    $LogString = json_encode($captureHandler->returnData());
    echo $LogString;
    exit();
} catch (Exception $e) {
    $ErrorData = ['error' => $e->getMessage()];
    $Logger->addLogData($ErrorData);
    $Logger->logToFile();
    $ErrorString = json_encode($ErrorData);
    die($ErrorString);
}
