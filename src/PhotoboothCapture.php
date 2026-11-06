<?php

namespace Photobooth;

use Photobooth\Logger\NamedLogger;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\ImageUtility;

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
    public function captureDemo(): void
    {
        $this->logger->debug('Capture Demo');

        try {
            $demoImages = ImageUtility::getDemoImages();
            $demoImage = $demoImages[array_rand($demoImages)];

            $extension = strtolower(pathinfo($demoImage, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg'], true)) {
                copy($demoImage, $this->tmpFile);
                return;
            }

            $imageHandler = new Image();
            $imageHandler->debugLevel = $this->debugLevel;
            $imageHandler->jpegQuality = 100;
            $imageResource = $imageHandler->createFromImage($demoImage);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Failed to create image resource from demo image.');
            }
            $imageHandler->saveJpeg($imageResource, $this->tmpFile);
            unset($imageResource);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load demo image!', ['error' => $e->getMessage()]);
            echo json_encode(['error' => 'Failed to load demo image!']);
            die();
        }
    }

    /**
     * Capture an image from canvas data.
     * @param string $data
     */
    public function captureCanvas($data): void
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
                if (!$im instanceof \GdImage) {
                    throw new \Exception('Failed to create image resource from tmp image.');
                }
                $imageHandler->debugLevel = $this->debugLevel;
                $imageHandler->jpegQuality = 100;
                switch ($this->flipImage) {
                    case 'flip-horizontal':
                        imageflip($im, IMG_FLIP_HORIZONTAL);
                        break;
                    case 'flip-vertical':
                        imageflip($im, IMG_FLIP_VERTICAL);
                        break;
                    case 'flip-both':
                        imageflip($im, IMG_FLIP_BOTH);
                        break;
                    default:
                        break;
                }
                $imageHandler->saveJpeg($im, $this->tmpFile);
                unset($im);
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
    public function captureWithCmd(): void
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
    public function returnData(): array
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
            $data = [
                'success' => $this->style,
                'file' => $this->fileName
            ];
        }
        $this->logger->debug('returnData', $data);
        return $data;
    }
}
