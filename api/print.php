<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Processor\PrintProcessor;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));
$processor = null;

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
    $vars['randomName'] = $imageHandler->createNewFilename('random');
    $vars['fileName'] = $_GET['filename'];
    $vars['uniqueName'] = substr($vars['fileName'], 0, -4) . '-' . $vars['randomName'];
    $vars['sourceFile'] = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $vars['fileName'];
    $vars['printFile'] = FolderEnum::PRINT->absolute() . DIRECTORY_SEPARATOR . $vars['uniqueName'];

    $status = false;

    // exit with error if file does not exist
    if (!file_exists($vars['sourceFile'])) {
        throw new \Exception('File ' . $vars['fileName'] . ' not found.');
    }
} catch (\Exception $e) {
    // Handle the exception
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
    echo json_encode($data);
    die();
}

$privatePrintApi = PathUtility::getAbsolutePath('private/api/print.php');
if (is_file($privatePrintApi)) {
    $logger->debug('Using private/api/print.php.');

    try {
        include $privatePrintApi;
    } catch (\Exception $e) {
        $logger->error('Error (private print API): ' . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
        die();
    }
}

if (!file_exists($vars['printFile'])) {
    try {
        $source = $imageHandler->createFromImage($vars['sourceFile']);
        if (!$source) {
            throw new \Exception('Invalid image resource');
        }
        if (class_exists('Photobooth\Processor\PrintProcessor')) {
            $processor = new PrintProcessor($imageHandler, $logger, $printManager, $vars, $config);
        }
        if ($processor !== null && $processor instanceof PrintProcessor && method_exists($processor, 'preProcessing')) {
            list($imageHandler, $vars, $config, $source) = $processor->preProcessing($imageHandler, $vars, $config, $source);
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
                $imageHandler->qrUrl = $config['ftp']['processedTemplate'] . DIRECTORY_SEPARATOR . $vars['fileName'];
            } elseif ($config['qr']['append_filename']) {
                $imageHandler->qrUrl = PathUtility::getPublicPath($config['qr']['url'] . $vars['fileName'], true);
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

        if ($processor !== null && $processor instanceof PrintProcessor && method_exists($processor, 'postProcessing')) {
            list($imageHandler, $vars, $config, $source) = $processor->postProcessing($imageHandler, $vars, $config, $source);
        }
        $imageHandler->jpegQuality = 100;
        if (!$imageHandler->saveJpeg($source, $vars['printFile'])) {
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
$cmd = sprintf($config['commands']['print'], $vars['printFile']);
$cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

exec($cmd, $output, $returnValue);

$printManager->addToPrintDb($vars['fileName'], $vars['uniqueName']);

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
