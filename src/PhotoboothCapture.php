<?php

namespace Photobooth;

use Photobooth\Logger\NamedLogger;
use Photobooth\Service\LoggerService;

/**
 * Class PhotoboothCapture
 */
class PhotoboothCapture
{
    public string $style;
    public string $fileName;
    public string $tmpFile;
    public string $collageSubFile;
    public int $collageNumber;
    public int $collageLimit;
    public string $demoFolder = __DIR__ . '/../resources/img/demo/';
    public string $flipImage = 'off';
    public string $captureCmd;
    public NamedLogger $logger;
    public int $debugLevel = 1;

    /**
     * PhotoboothCapture constructor.
     */
    public function __construct()
    {
        $this->logger = LoggerService::getInstance()->getLogger('main');
        $this->logger->debug(self::class);
    }

    /**
     * Capture a demo image.
     */
    public function captureDemo()
    {
        $this->logger->debug('Capture Demo', [
            'demoFolder' => $this->demoFolder
        ]);
        $demoFolder = $this->demoFolder;
        $devImg = array_diff(scandir($demoFolder), ['.', '..']);
        copy($demoFolder . $devImg[array_rand($devImg)], $this->tmpFile);
    }

    /**
     * Capture an image from canvas data.
     * @param string $data
     */
    public function captureCanvas($data)
    {
        $this->logger->debug('Capture Canvas');
        try {
            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            file_put_contents($this->tmpFile, $data);

            if ($this->flipImage != 'off') {
                $imageHandler = new Image();
                $im = $imageHandler->createFromImage($this->tmpFile);
                $imageHandler->debugLevel = $this->debugLevel;
                $imageHandler->jpegQuality = 100;
                switch ($this->flipImage) {
                    case 'flip-horizontal':
                        imageflip($im, IMG_FLIP_HORIZONTAL);
                        break;
                    case 'flip-vertical':
                        imageflip($im, IMG_FLIP_VERTICAL);
                        break;
                    default:
                        break;
                }
                $imageHandler->saveJpeg($im, $this->tmpFile);
                imagedestroy($im);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
            die();
        }
    }

    /**
     * Capture an image or video using a command.
     */
    public function captureWithCmd()
    {
        $this->logger->debug('Capture with CMD', [
            'cmd' => $this->captureCmd,
            'tmpFile' => $this->tmpFile,
        ]);
        //gphoto must be executed in a dir with write permission for other commands we stay in the api dir
        if (substr($this->captureCmd, 0, strlen('gphoto')) === 'gphoto') {
            chdir(dirname($this->tmpFile));
        }
        $cmd = sprintf($this->captureCmd, $this->tmpFile);
        $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

        exec($cmd, $output, $returnValue);

        if ($returnValue && ($this->debugLevel > 1 || $this->style === 'video')) {
            $data = [
                'error' => 'Capture command returned an error code.',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            $this->logger->error('error', $data);
            if ($this->style === 'video') {
                echo json_encode($data);
                die();
            }
        }

        if ($this->style === 'video') {
            $i = 0;
            $processingTime = 300;
            while ($i < $processingTime) {
                if (file_exists($this->tmpFile)) {
                    break;
                } else {
                    $i++;
                    usleep(100000);
                }
            }
        }

        if (!file_exists($this->tmpFile)) {
            $data = [
                'error' => 'File was not created',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            if ($this->style === 'video') {
                // remove all files that were created - all filenames start with the videos name
                exec('rm -f ' . $this->tmpFile . '*');
            }
            $this->logger->error('error', $data);
            echo json_encode($data);
            die();
        }
    }

    /**
     * Return information about the successful capture process
     */
    public function returnData()
    {
        if ($this->style === 'collage') {
            $data = [
                'success' => 'collage',
                'file' => $this->fileName,
                'collage_file' => $this->collageSubFile,
                'current' => $this->collageNumber,
                'limit' => $this->collageLimit,
            ];
        } else {
            $data = ['success' => $this->style, 'file' => $this->fileName];
        }
        $this->logger->debug('returnData', $data);
        return $data;
    }
}
