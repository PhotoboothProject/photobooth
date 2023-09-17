<?php

require_once __DIR__ . '/log.php';
require_once __DIR__ . '/image.php';

/**
 * Class PhotoboothCapture
 */
class PhotoboothCapture
{
    /** @var string $style */
    public $style;
    /** @var string $fileName */
    public $fileName;
    /** @var string $tmpFile */
    public $tmpFile;
    /** @var string $collageSubFile */
    public $collageSubFile;
    /** @var int $collageNumber */
    public $collageNumber;
    /** @var int $collageLimit */
    public $collageLimit;
    /** @var string $demoFolder */
    public $demoFolder = __DIR__ . '/../resources/img/demo/';
    /** @var string $flipImage */
    public $flipImage = 'off';
    /** @var string $captureCmd */
    public $captureCmd;
    /** @var DataLogger|null $logger */
    public $logger = null;
    /** @var int $debugLevel */
    public $debugLevel = 1;

    /**
     * PhotoboothCapture constructor.
     * @param DataLogger|null $logger
     */
    public function __construct($logger = null)
    {
        if ($logger == null || !is_object($this->logger)) {
            $this->logger = new DataLogger(PHOTOBOOTH_LOG);
            $this->logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
        }
    }

    /**
     * Capture a demo image.
     */
    public function captureDemo()
    {
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
        try {
            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            file_put_contents($this->tmpFile, $data);

            if ($this->flipImage != 'off') {
                try {
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
                } catch (Exception $e) {
                    $ErrorData = ['error' => $e->getMessage()];
                    $this->logger->addLogData($ErrorData);
                    if ($this->debugLevel > 1) {
                        $this->logger->logToFile();
                        $ErrorString = json_encode($ErrorData);
                        die($ErrorString);
                    }
                }
            }
        } catch (Exception $e) {
            $ErrorData = ['error' => $e->getMessage()];
            $this->logger->addLogData($ErrorData);
            $this->logger->logToFile();
            $ErrorString = json_encode($ErrorData);
            die($ErrorString);
        }
    }

    /**
     * Capture an image or video using a command.
     */
    public function captureWithCmd()
    {
        //gphoto must be executed in a dir with write permission for other commands we stay in the api dir
        if (substr($this->captureCmd, 0, strlen('gphoto')) === 'gphoto') {
            chdir(dirname($this->tmpFile));
        }
        $cmd = sprintf($this->captureCmd, $this->tmpFile);
        $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

        exec($cmd, $output, $returnValue);

        if ($returnValue && ($this->debugLevel > 1 || $this->style === 'video')) {
            $ErrorData = [
                'error' => 'Capture command returned an error code.',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            $this->logger->addLogData($ErrorData);
            if ($this->style === 'video') {
                $this->logger->logToFile();
                $ErrorString = json_encode($ErrorData);

                die($ErrorString);
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
            $ErrorData = [
                'error' => 'File was not created',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            if ($this->style === 'video') {
                // remove all files that were created - all filenames start with the videos name
                exec('rm -f ' . $this->tmpFile . '*');
            }
            $this->logger->addLogData($ErrorData);
            $this->logger->logToFile();
            $ErrorString = json_encode($ErrorData);

            die($ErrorString);
        }
    }

    /**
     * Return information about the successful capture process
     */
    public function returnData()
    {
        if ($this->style === 'collage') {
            $LogData = [
                'success' => 'collage',
                'file' => $this->fileName,
                'collage_file' => $this->collageSubFile,
                'current' => $this->collageNumber,
                'limit' => $this->collageLimit,
            ];
        } else {
            $LogData = ['success' => $this->style, 'file' => $this->fileName];
        }
        if ($this->debugLevel > 1) {
            $this->logger->addLogData($LogData);
            $this->logger->logToFile();
        }
        return $LogData;
    }
}
