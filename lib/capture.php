<?php
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/image.php';

class PhotoboothCapture {
    public $style;
    public $fileName;
    public $tmpFile;
    public $collageSubFile;
    public $collageNumber;
    public $collageLimit;
    public $demoFolder = __DIR__ . '/../resources/img/demo/';
    public $flipImage = 'off';
    public $captureCmd;
    public $logger = null;
    public $debugLevel = 1;

    public function __construct($logger = null) {
        if ($logger == null || !is_object($this->logger)) {
            $this->logger = new DataLogger(PHOTOBOOTH_LOG);
            $this->logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
        }
    }

    public function captureDemo() {
        $demoFolder = $this->demoFolder;
        $devImg = array_diff(scandir($demoFolder), ['.', '..']);
        copy($demoFolder . $devImg[array_rand($devImg)], $this->tmpFile);
    }

    public function captureCanvas($data) {
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

    public function captureWithCmd() {
        //gphoto must be executed in a dir with write permission for other commands we stay in the api dir
        if (substr($this->captureCmd, 0, strlen('gphoto')) === 'gphoto') {
            chdir(dirname($this->tmpFile));
        }
        $cmd = sprintf($this->captureCmd, $this->tmpFile);
        $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

        exec($cmd, $output, $returnValue);

        if ($returnValue && $this->debugLevel > 1) {
            $ErrorData = [
                'error' => 'Take picture command returned an error code',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            $this->logger->addLogData($ErrorData);
        }

        if (!file_exists($this->tmpFile)) {
            $ErrorData = [
                'error' => 'File was not created',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => $output,
            ];
            $this->logger->addLogData($ErrorData);
            $this->logger->logToFile();
            $ErrorString = json_encode($ErrorData);

            die($ErrorString);
        }
    }

    public function returnData() {
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
        $LogString = json_encode($LogData);
        die($LogString);
    }
}
