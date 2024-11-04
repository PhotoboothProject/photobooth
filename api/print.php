<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

try {
    if (empty($_GET['filename'])) {
        throw new \Exception('No file provided!');
    }

    $printManager = PrintManagerService::getInstance();
    if ($printManager->isPrintLocked()) {
        throw new \Exception($config['print']['limit_msg']);
    }

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $random = $imageHandler->createNewFilename('random');
    $filename = $_GET['filename'];
    $uniquename = substr($filename, 0, -4) . '-' . $random;
    $filename_source = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $filename;
    $filename_print = FolderEnum::PRINT->absolute() . DIRECTORY_SEPARATOR . $uniquename;

    $status = false;

    // exit with error if file does not exist
    if (!file_exists($filename_source)) {
        throw new \Exception('File ' . $filename . ' not found.');
    }
} catch (\Exception $e) {
    // Handle the exception
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
    echo json_encode($data);
    die();
}

if (!file_exists($filename_print)) {
    try {
        $source = $imageHandler->createFromImage($filename_source);
        if (!$source) {
            throw new \Exception('Invalid image resource');
        }
        // rotate image if needed
        if (imagesx($source) > imagesy($source) || $config['print']['no_rotate'] === true) {
            $imageHandler->qrRotate = false;
        } else {
            $source = imagerotate($source, 90, 0);
            $imageHandler->qrRotate = true;
            if (!$source) {
                throw new \Exception('Cannot rotate image resource.');
            }
        }

        if ($config['print']['print_frame']) {
            $imageHandler->framePath = $config['print']['frame'];
            $imageHandler->frameExtend = false;
            $source = $imageHandler->applyFrame($source);
            if (!$source instanceof \GdImage) {
                throw new \Exception('Failed to apply frame to image resource.');
            }
        }

        if ($config['print']['qrcode']) {
            // create qr code
            if ($config['ftp']['enabled'] && $config['ftp']['useForQr'] && isset($config['ftp']['processedTemplate'])) {
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
            if (!$qrCode instanceof \GdImage) {
                throw new \Exception('Cannot create QR Code resource.');
            }
            $source = $imageHandler->applyQr($qrCode, $source);
            if (!$source instanceof \GdImage) {
                throw new \Exception('Cannot apply QR Code to image resource.');
            }
            unset($qrCode);
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
            if (!$source instanceof \GdImage) {
                throw new \Exception('Failed to apply text to image resource.');
            }
        }

        if ($config['print']['crop']) {
            $source = $imageHandler->resizeCropImage($source, $config['print']['crop_width'], $config['print']['crop_height']);
            if (!$source instanceof \GdImage) {
                throw new \Exception('Failed to crop image resource.');
            }
        }

        $imageHandler->jpegQuality = 100;
        if (!$imageHandler->saveJpeg($source, $filename_print)) {
            throw new \Exception('Cannot save print image.');
        }

        // clear cache
        unset($source);
    } catch (\Exception $e) {
        // Try to clear cache
        if ($source instanceof \GdImage) {
            unset($source);
        }

        $data = ['error' => $e->getMessage()];
        $logger->error($e->getMessage());
        echo json_encode($data);
        die();
    }
}

// print image
$status = 'ok';
$cmd = sprintf($config['commands']['print'], $filename_print);
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
