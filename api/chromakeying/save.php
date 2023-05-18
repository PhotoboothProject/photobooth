<?php
header('Content-Type: application/json');

if (!isset($_POST['imgData']) || empty($_POST['imgData'])) {
    http_response_code(400);
    $logData = [
        'success' => false,
        'error' => 'imgData not set or empty.',
    ];
    $logString = json_encode($logData);
    die($logString);
}

require_once '../../lib/config.php';
require_once '../../lib/db.php';
require_once '../../lib/image.php';
require_once '../../lib/log.php';

$imageHandler = new Image();
$imageHandler->debugLevel = $config['dev']['loglevel'];
$imageHandler->jpegQuality = $config['jpeg_quality']['image'];

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

$file = $imageHandler->createNewFilename($config['picture']['naming']);

$database = new DatabaseManager();
$database->db_file = DB_FILE;
$database->file_dir = IMG_DIR;

if ($config['database']['file'] != 'db') {
    $file = $config['database']['file'] . '_' . $file;
}

$filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $file;
$filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $file;
$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);

try {
    $img = $_POST['imgData'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    $imageResource = imagecreatefromstring($data);
    if (!$imageResource) {
        throw new Exception('Failed to create image from data.');
    }
    if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
        throw new Exception('Failed to save ' . $filename_photo);
    }
    if (!$imageHandler->saveJpeg($imageResource, $filename_keying)) {
        $imageHandler->errorCount++;
        $imageHandler->errorLog[] = ['Warning' => 'Failed to save chroma image copy.'];
    }

    // image scale, create thumbnail
    $thumb_size = substr($config['picture']['thumb_size'], 0, -2);
    $imageHandler->resizeMaxWidth = $thumb_size;
    $imageHandler->resizeMaxHeight = $thumb_size;
    $thumbResource = $imageHandler->resizeImage($imageResource);

    $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
    if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
        $imageHandler->errorCount++;
        $imageHandler->errorLog[] = ['Warning' => 'Failed to create thumbnail.'];
    }

    // clear cache
    if (is_resource($thumbResource)) {
        imagedestroy($thumbResource);
    }

    imagedestroy($imageResource);

    // insert into database
    if ($config['database']['enabled']) {
        if (!$database->appendContentToDB($file)) {
            $imageHandler->errorCount++;
            $imageHandler->errorLog[] = ['Warning' => 'Failed to add ' . $file . ' to database.'];
        }
    }

    // Change permissions
    $picture_permissions = $config['picture']['permissions'];
    if (!chmod($filename_photo, octdec($picture_permissions))) {
        $imageHandler->errorCount++;
        $imageHandler->errorLog[] = ['Warning' => 'Failed to change picture permissions.'];
    }
} catch (Exception $e) {
    // Try to clear cache
    if (is_resource($thumbResource)) {
        imagedestroy($thumbResource);
    }
    if (is_resource($imageResource)) {
        imagedestroy($imageResource);
    }
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $Logger->addLogData($imageHandler->errorLog);
    }
    $ErrorData = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
    $Logger->addLogData($ErrorData);
    $Logger->logToFile();
    $ErrorString = json_encode($ErrorData);
    die($ErrorString);
}

// send imagename to frontend
$LogData = [
    'success' => true,
    'filename' => $file,
];
if ($config['dev']['loglevel'] > 1) {
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $Logger->addLogData($imageHandler->errorLog);
    }
    $Logger->addLogData($LogData);
    $Logger->logToFile();
}
$LogString = json_encode($LogData);
die($LogString);
