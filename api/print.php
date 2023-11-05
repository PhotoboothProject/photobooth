<?php

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance();
$logger->debug(basename($_SERVER['PHP_SELF']));

try {
    if (empty($_GET['filename'])) {
        throw new Exception('No file provided!');
    }

    $printManager = PrintManagerService::getInstance();
    if ($printManager->isPrintLocked()) {
        throw new Exception($config['print']['limit_msg']);
    }

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $random = $imageHandler->createNewFilename('random');
    $filename = $_GET['filename'];
    $uniquename = substr($filename, 0, -4) . '-' . $random;
    $filename_source = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $filename;
    $filename_print = $config['foldersAbs']['print'] . DIRECTORY_SEPARATOR . $uniquename;

    $status = false;

    // exit with error if file does not exist
    if (!file_exists($filename_source)) {
        throw new Exception('File ' . $filename . ' not found.');
    }

    // Only jpg/jpeg are supported
    $imginfo = getimagesize($filename_source);
    $mimetype = $imginfo['mime'];
    if ($mimetype != 'image/jpg' && $mimetype != 'image/jpeg') {
        throw new Exception('The source file type ' . $mimetype . ' is not supported');
    }
} catch (Exception $e) {
    // Handle the exception
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
    echo json_encode($data);
    die();
}

if (!file_exists($filename_print)) {
    try {
        $source = $imageHandler->createFromImage($filename_source);

        // rotate image if needed
        list($width, $height) = getimagesize($filename_source);
        if ($width > $height || $config['print']['no_rotate'] === true) {
            $imageHandler->qrRotate = false;
        } else {
            $source = imagerotate($source, 90, 0);
            $imageHandler->qrRotate = true;
            if (!$source) {
                throw new Exception('Can\'t rotate image resource.');
            }
        }

        if ($config['print']['print_frame']) {
            $imageHandler->framePath = $config['print']['frame'];
            $imageHandler->frameExtend = false;
            $source = $imageHandler->applyFrame($source);
            if (!$source) {
                throw new Exception('Failed to apply frame to image resource.');
            }
        }

        if ($config['print']['qrcode']) {
            // create qr code
            if ($config['ftp']['enabled'] && $config['ftp']['useForQr']) {
                $imageHandler->qrUrl = $config['ftp']['processedTemplate'] . DIRECTORY_SEPARATOR . $filename;
            } elseif ($config['qr']['append_filename']) {
                $imageHandler->qrUrl = PathUtility::getPublicPath($config['qr']['url'] . $filename, true);
            } else {
                $imageHandler->qrUrl = PathUtility::getPublicPath($config['qr']['url'], true);
            }
            $imageHandler->qrSize = $config['print']['qrSize'];
            $imageHandler->qrMargin = $config['print']['qrMargin'];
            $imageHandler->qrColor = $config['print']['qrBgColor'];
            $imageHandler->qrOffset = $config['print']['qrOffset'];
            $imageHandler->qrPosition = $config['print']['qrPosition'];

            $qrCode = $imageHandler->createQr();
            if (!$qrCode) {
                throw new Exception('Can\'t create QR Code resource.');
            }
            $source = $imageHandler->applyQr($qrCode, $source);
            if (!$source) {
                throw new Exception('Can\'t apply QR Code to image resource.');
            }
            imagedestroy($qrCode);
        }

        if ($config['textonprint']['enabled']) {
            $imageHandler->fontSize = $config['textonprint']['font_size'];
            $imageHandler->fontRotation = $config['textonprint']['rotation'];
            $imageHandler->fontLocationX = $config['textonprint']['locationx'];
            $imageHandler->fontLocationY = $config['textonprint']['locationy'];
            $imageHandler->fontColor = $config['textonprint']['font_color'];
            $imageHandler->fontPath = $config['textonprint']['font'];
            $imageHandler->textLine1 = $config['textonprint']['line1'];
            $imageHandler->textLine2 = $config['textonprint']['line2'];
            $imageHandler->textLine3 = $config['textonprint']['line3'];
            $imageHandler->textLineSpacing = $config['textonprint']['linespace'];

            $source = $imageHandler->applyText($source);
            if (!$source) {
                throw new Exception('Failed to apply text to image resource.');
            }
        }

        if ($config['print']['crop']) {
            $imageHandler->resizeMaxWidth = $config['print']['crop_width'];
            $imageHandler->resizeMaxHeight = $config['print']['crop_height'];
            $source = $imageHandler->resizeCropImage($source);
            if (!$source) {
                throw new Exception('Failed to crop image resource.');
            }
        }

        $imageHandler->jpegQuality = 100;
        if (!$imageHandler->saveJpeg($source, $filename_print)) {
            throw new Exception('Can\'t save print image.');
        }

        // clear cache
        imagedestroy($source);
    } catch (Exception $e) {
        // Try to clear cache
        if ($source instanceof GdImage) {
            imagedestroy($source);
        }

        $data = ['error' => $e->getMessage()];
        $logger->error($e->getMessage());
        echo json_encode($data);
        die();
    }
}

// print image
$status = 'ok';
$cmd = sprintf($config['print']['cmd'], $filename_print);
$cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

exec($cmd, $output, $returnValue);

$printManager->addToPrintDb($filename, $uniquename);

$linecount = 0;
if ($config['print']['limit'] > 0) {
    $linecount = $printManager->getPrintCountFromDB();
    $linecount = $linecount ? $linecount : 0;
    if ($linecount % $config['print']['limit'] == 0) {
        if ($printManager->lockPrint()) {
            $status = 'locking';
        } else {
            $logger->error('Error creating the file ' . $printManager->printLockFile);
        }
    }
    file_put_contents($printManager->printCounter, $linecount);
}

$data = [
    'status' => $status,
    'count' => $linecount,
    'msg' => $cmd,
    'returnValue' => $returnValue,
    'output' => $output,
];
$logger->debug('data', $data);
echo json_encode($data);
exit();
