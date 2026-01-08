<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Image;
use Photobooth\Processor\PrintProcessor;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));
$session         = $_SESSION;
$csrfKey         = 'csrf';
$csrfToken       = $_SESSION[$csrfKey] ?? '';
$rateLimitWindow = 60;
$rateLimitMax    = 10;
$processor = null;
$linecount = 0;
$data = [];

try {
    $incomingToken = $_GET[$csrfKey] ?? '';
    if (!hash_equals((string)$csrfToken, (string)$incomingToken)) {
        throw new \Exception('Invalid CSRF token');
    }

    // Simple per-session rate limit for print requests
    $now = time();
    if (!isset($_SESSION['print']) || !is_array($_SESSION['print'])) {
        $_SESSION['print'] = ['count' => 0, 'window' => $now];
    }
    if (($now - ($_SESSION['print']['window'] ?? 0)) > $rateLimitWindow) {
        $_SESSION['print'] = ['count' => 0, 'window' => $now];
    }
    if (($_SESSION['print']['count'] ?? 0) >= $rateLimitMax) {
        throw new \Exception('Rate limit exceeded, please wait a moment and retry');
    }

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
    $vars['fileName'] = basename($_GET['filename']);
    if ($vars['fileName'] === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $vars['fileName'])) {
        throw new \Exception('Invalid filename provided.');
    }
    $vars['copies'] = max(1, (int) $_GET['copies']);
    $vars['uniqueName'] = substr($vars['fileName'], 0, -4) . '-' . $vars['randomName'];
    $vars['sourceFile'] = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $vars['fileName'];
    $vars['printFile'] = FolderEnum::PRINT->absolute() . DIRECTORY_SEPARATOR . $vars['uniqueName'];

    // exit with error if file does not exist
    if (!file_exists($vars['sourceFile'])) {
        throw new \Exception('File ' . $vars['fileName'] . ' not found.');
    }

    if ($config['print']['limit'] > 0) {
        $linecount = $printManager->getPrintCountFromDB();
        $linecount = $linecount ? $linecount : 0;

        $limit = $config['print']['limit'];
        $newCount = $linecount + $vars['copies'];

        $nextThreshold = ceil($linecount / $limit) * $limit;
        if ($nextThreshold == 0) {
            $nextThreshold = $limit;
        }

        if ($newCount > $nextThreshold) {
            throw new \Exception('Unable to print ' . $vars['copies'] . ' copies');
        }
    }

    // record successful validation for rate limiting
    $_SESSION['print']['count'] = ($_SESSION['print']['count'] ?? 0) + 1;
} catch (\Exception $e) {
    // Handle the exception
    $data = [
        'status' => 'error',
        'error' => $e->getMessage(),
    ];

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
        $data = [
            'status' => 'error',
            'error' => $e->getMessage(),
        ];
        echo json_encode($data);
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
            [$imageHandler, $vars, $config, $source] = $processor->preProcessing($imageHandler, $vars, $config, $source);
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
            $url = $config['qr']['url'];
            if ($config['ftp']['enabled'] && $config['ftp']['useForQr']) {
                $remoteStorageService = RemoteStorageService::getInstance();
                $url = $remoteStorageService->getWebpageUri();
                if ($config['qr']['append_filename']) {
                    $url .= '/images/';
                }
            }
            if ($config['qr']['append_filename']) {
                $url .= $vars['fileName'];
            }
            $imageHandler->qrUrl = PathUtility::getPublicPath($url, true);
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
            [$imageHandler, $vars, $config, $source] = $processor->postProcessing($imageHandler, $vars, $config, $source);
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

        $logger->error($e->getMessage());

        $data = [
            'status' => 'error',
            'error' => $e->getMessage(),
        ];
        echo json_encode($data);
        die();
    }
}

// print image
$status = 'ok';

if ($config['print']['max_multi'] > 1) {
    $cmd = sprintf(
        $config['commands']['print'],
        (int) $vars['copies'],
        escapeshellarg($vars['printFile'])
    );
} else {
    $cmd = sprintf(
        $config['commands']['print'],
        escapeshellarg($vars['printFile'])
    );
}
$logger->info($cmd);
$cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

exec($cmd, $output, $returnValue);

if ($returnValue !== 0) {
    $status = 'error';

    switch ($returnValue) {
        case 1:
            $error = 'General error. Check printer status or file path.';
            break;
        case 2:
            $error = 'Misuse of command. Possibly wrong syntax or options.';
            break;
        case 126:
            $error = 'Command invoked cannot execute. Check permissions.';
            break;
        case 127:
            $error = "Command not found. Check if 'lp' is installed and in PATH.";
            break;
        case 238:
            $status = 'queued';
            $error = 'Image added to print queue.';
            break;
        default:
            $error = "Unknown error (exit code $returnValue).";
    }

    $logger->error($error);
    $data['error'] = $error;
}

$outputMessage = implode("\n", (array) ($output));
if (trim($outputMessage) === '') {
    $outputMessage = '(no output)';
}

$logger->debug('Print command: ' . $cmd);
$logger->debug('Print command output: ' . $outputMessage);
$logger->debug('Return value: ' . $returnValue);

if ($status === 'ok') {
    if ($vars['copies'] > 1) {
        for ($i = 1; $i <= $vars['copies']; $i++) {
            $printManager->addToPrintDb($vars['fileName'], $vars['uniqueName'] . '-' . $i);
        }
    } else {
        $printManager->addToPrintDb($vars['fileName'], $vars['uniqueName']);
    }

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
}

$data['status'] = $status;
$data['count'] = $linecount;

$logger->debug('data', $data);
echo json_encode($data);
exit();
