<?php

/** @var array $config */

require_once '../../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\FileDelete;
use Photobooth\PhotoboothCapture;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\ImageUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

if (!isset($_POST['imgData']) || empty($_POST['imgData'])) {
    http_response_code(400);
    $data = [
        'success' => false,
        'error' => 'imgData not set or empty.',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    die();
}

$imageHandler = new Image();
$imageHandler->debugLevel = $config['dev']['loglevel'];

$saveCopy = false;
$applyEffects = false;
if (!isset($_POST['file']) || empty($_POST['file'])) {
    $file = $imageHandler->createNewFilename($config['picture']['naming']);
} else {
    $saveCopy = true;
    $applyEffects = true;
    $file = $_POST['file'];
}

$database = DatabaseManagerService::getInstance();
if ($config['database']['file'] != 'db') {
    $file = $config['database']['file'] . '_' . $file;
}

if ($saveCopy) {
    $file = $imageHandler->createNewFilename($config['picture']['naming']);
    if (!$config['keying']['show_all']) {
        $database->deleteContentFromDB($_POST['file']);

        if (!$config['picture']['keep_original']) {
            $paths = [
                FolderEnum::IMAGES->absolute(),
                FolderEnum::THUMBS->absolute(),
                FolderEnum::KEYING->absolute(),
                FolderEnum::TEMP->absolute(),
            ];
            $delete = new FileDelete($_POST['file'], $paths);
            $delete->deleteFiles();
            $logger->debug('delete', $delete->getLogData());
        }
    }
}

$filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $file;
$filename_photo = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $file;
$filename_thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $file;
$filename_keying = FolderEnum::KEYING->absolute() . DIRECTORY_SEPARATOR . $file;
$picture_permissions = $config['picture']['permissions'];
$thumb_size = substr($config['picture']['thumb_size'], 0, -2);

try {
    $captureHandler = new PhotoboothCapture();
    $captureHandler->debugLevel = $config['dev']['loglevel'];
    $captureHandler->style = 'chroma';
    $captureHandler->fileName = $file;
    $captureHandler->tmpFile = $filename_tmp;
    $captureHandler->flipImage = $config['picture']['flip'];
    $captureHandler->captureCanvas($_POST['imgData']);

    $imageResource = $imageHandler->createFromImage($filename_tmp);
    if (!$imageResource instanceof \GdImage) {
        throw new \Exception('Failed to create image from data.');
    }

    if ($applyEffects) {
        // apply filter
        if ($config['filters']['defaults'] != ImageFilterEnum::PLAIN) {
            try {
                ImageUtility::applyFilter($config['filters']['defaults'], $imageResource);
                $imageHandler->imageModified = true;
            } catch (\Exception $e) {
                throw new \Exception('Error applying image filter.');
            }
        }

        if ($config['picture']['polaroid_effect']) {
            $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
            $imageResource = $imageHandler->effectPolaroid($imageResource);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error applying polaroid effect.');
            }
        }

        if ($config['picture']['take_frame']) {
            $imageHandler->framePath = $config['picture']['frame'];
            $imageHandler->frameExtend = $config['picture']['extend_by_frame'];
            if ($config['picture']['extend_by_frame']) {
                $imageHandler->frameExtendLeft = $config['picture']['frame_left_percentage'];
                $imageHandler->frameExtendRight = $config['picture']['frame_right_percentage'];
                $imageHandler->frameExtendBottom = $config['picture']['frame_bottom_percentage'];
                $imageHandler->frameExtendTop = $config['picture']['frame_top_percentage'];
            }
            $imageResource = $imageHandler->applyFrame($imageResource);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error applying frame to image resource.');
            }
        }

        if ($config['picture']['rotation'] !== '0') {
            $imageResource = $imageHandler->rotateResizeImage(
                image: $imageResource,
                degrees: $config['picture']['rotation']
            );
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error resizing resource.');
            }
        }
    }
    $chroma_size = intval(substr($config['keying']['size'], 0, -2));
    $chromaCopyResource = $imageHandler->resizeImage($imageResource, $chroma_size);
    if ($chromaCopyResource instanceof \GdImage) {
        $imageHandler->jpegQuality = $config['jpeg_quality']['chroma'];
        if (!$imageHandler->saveJpeg($chromaCopyResource, $filename_keying)) {
            $imageHandler->addErrorData('Warning: Failed to save chroma image copy.');
        }
    } else {
        $imageHandler->addErrorData('Warning: Failed to resize image copy.');
    }
    if ($chromaCopyResource instanceof \GdImage) {
        unset($chromaCopyResource);
    }

    if ($applyEffects) {
        if ($config['textonpicture']['enabled']) {
            $imageHandler->fontSize = $config['textonpicture']['font_size'];
            $imageHandler->fontRotation = $config['textonpicture']['rotation'];
            $imageHandler->fontLocationX = $config['textonpicture']['locationx'];
            $imageHandler->fontLocationY = $config['textonpicture']['locationy'];
            $imageHandler->fontColor = $config['textonpicture']['font_color'];
            $imageHandler->fontPath = $config['textonpicture']['font'];
            $imageHandler->textLine1 = $config['textonpicture']['line1'];
            $imageHandler->textLine2 = $config['textonpicture']['line2'];
            $imageHandler->textLine3 = $config['textonpicture']['line3'];
            $imageHandler->textLineSpacing = $config['textonpicture']['linespace'];
            $imageResource = $imageHandler->applyText($imageResource);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error applying text to image resource.');
            }
        }
    }

    // image scale, create thumbnail
    $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
    $thumbResource = $imageHandler->resizeImage($imageResource, $thumb_size);
    if ($thumbResource instanceof \GdImage) {
        $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
        if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
            $imageHandler->addErrorData('Warning: Failed to create thumbnail.');
        }
    } else {
        throw new \Exception('Error: Failed to resize thumbnail.');
    }

    if ($thumbResource instanceof \GdImage) {
        unset($thumbResource);
    }

    if ($imageHandler->imageModified) {
        $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
        if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
            throw new \Exception('Failed to save image.');
        }
    } else {
        if (!copy($filename_tmp, $filename_photo)) {
            throw new \Exception('Failed to copy photo.');
        }
    }

    unset($imageResource);

    // insert into database
    if ($config['database']['enabled']) {
        $database->appendContentToDB($file);
    }

    // Change permissions
    $picture_permissions = $config['picture']['permissions'];
    if (!chmod($filename_photo, (int)octdec($picture_permissions))) {
        $imageHandler->addErrorData('Warning: Failed to change picture permissions.');
    }
} catch (\Exception $e) {
    // Try to clear cache
    if (isset($thumbResource) && $thumbResource instanceof \GdImage) {
        unset($thumbResource);
    }
    if ($imageResource instanceof \GdImage) {
        unset($imageResource);
    }
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $logger->debug('imageHandler->errorLog', $imageHandler->errorLog);
    }
    $logger->debug($e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
    die();
}

// send imagename to frontend
$data = [
    'success' => true,
    'filename' => $file,
];
if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
    $logger->debug('imageHandler->errorLog', $imageHandler->errorLog);
}
echo json_encode($data);
exit();
