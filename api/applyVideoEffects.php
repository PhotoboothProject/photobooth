<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Factory\CollageConfigFactory;
use Photobooth\Image;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$database = DatabaseManagerService::getInstance();

try {
    if (empty($_POST['file'])) {
        throw new \Exception('No file provided');
    }

    $file = $_POST['file'];

    $tmpFolder = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR;
    $imageFolder = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR;
    $thumbsFolder = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR;
    $filenameTmp = $tmpFolder . $file;
    $filenameOutput = $imageFolder . $file;
    $filenameThumb = $thumbsFolder . $file;

    if (!file_exists($filenameTmp)) {
        throw new \Exception('Image doesn\'t exist.');
    }
} catch (\Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

$frames = [];
for ($i = 1; $i < 99; $i++) {
    $frameFilename = sprintf('%s-%02d.jpg', $filenameTmp, $i);
    if (file_exists($frameFilename)) {
        $frames[] = $frameFilename;
    } else {
        break;
    }
}

if (is_file(__DIR__ . '/../private/api/applyVideoEffects.php')) {
    $logger->info('Using private/api/applyVideoEffects.php.');
    include __DIR__ . '/../private/api/applyVideoEffects.php';
}

try {
    // If the video command created 4 images, create a cuttable collage (more flexibility to maybe come one day)
    $collageFilename = '';
    $images = [];
    if ($config['video']['collage'] && count($frames) === 4) {
        $collageFilename = sprintf('%s-collage.jpg', $file);
        $collageConfig = CollageConfigFactory::fromConfig($config);
        $collageConfig->collageLayout = '2x4-3';
        $collageConfig->collageTakeFrame = 'off';
        $collageConfig->collagePlaceholder = 'false';
        if (!Collage::createCollage($config, $frames, $collageFilename, $config['filters']['defaults'], $collageConfig)) {
            throw new \Exception('Could not create collage.');
        }
        $images[] = $collageFilename;
    }

    if (!$config['video']['collage_keep_images']) {
        foreach ($frames as $frame) {
            if (!unlink($frame)) {
                $logger->error('Error while deleting ' . $frame);
            }
        }
    } else {
        $images = array_merge($images, $frames);
    }

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];

    foreach ($images as $image) {
        $imageResource = $imageHandler->createFromImage($image);
        if (!$imageResource) {
            throw new \Exception('Error creating image resource.');
        }
        $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
        $thumbResource = $imageHandler->resizeImage($imageResource, $thumb_size);
        if ($thumbResource instanceof \GdImage) {
            $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
            if (!$imageHandler->saveJpeg($thumbResource, $thumbsFolder . basename($image))) {
                $imageHandler->addErrorData('Warning Failed to create thumbnail.');
            }
        } else {
            $imageHandler->addErrorData('Warning: Failed to resize thumbnail.');
        }

        if ($thumbResource instanceof \GdImage) {
            unset($thumbResource);
        }

        $newFile = $imageFolder . basename($image);

        $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
        if ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100) {
            if (!$imageHandler->saveJpeg($imageResource, $newFile)) {
                throw new \Exception('Failed to save image.');
            }
        } else {
            if (!copy($image, $newFile)) {
                throw new \Exception('Failed to copy photo.');
            }
        }
        unset($imageResource);

        if (!$config['picture']['keep_original']) {
            if (!unlink($image)) {
                $imageHandler->addErrorData('Warning: Failed to delete photo.');
            }
        }

        if ($config['database']['enabled']) {
            $database->appendContentToDB(basename($newFile));
        }
        $picture_permissions = $config['picture']['permissions'];
        if (!chmod($newFile, (int)octdec($picture_permissions))) {
            $imageHandler->addErrorData('Warning: Failed to change picture permissions.');
        }
    }

    if ($config['video']['collage_only']) {
        if ($collageFilename === '' || !file_exists($imageFolder . $collageFilename)) {
            throw new \Exception('Configured to save only Collage but collage file does not exist: ' . $collageFilename);
        }
        if (!$config['picture']['keep_original']) {
            if (!unlink($filenameTmp)) {
                $imageHandler->addErrorData('Warning: Failed to remove temporary photo.');
            }
        }
        $file = $collageFilename;
    } else {
        $cfilter = [];
        $additionalParams = '';
        if ($config['video']['effects'] !== 'None') {
            if ($config['video']['effects'] === 'boomerang') {
                // get second to last frame to prevent frame duplication
                $frames = shell_exec("ffprobe -v error -select_streams v:0 -count_packets \
        -show_entries stream=nb_read_packets -of csv=p=0 $filenameTmp");
                $secondToLastFrame = intval($frames) - 1;
                $logger->info('Seconds to last frame: ' . $secondToLastFrame);
                $cfilter[] = "[0]trim=start_frame=1:end_frame=$secondToLastFrame,setpts=PTS-STARTPTS,reverse[r];[0][r]concat=n=2:v=1:a=0";
            }
        }

        if ($config['video']['gif']) {
            $cfilter[] = 'split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse';
            $additionalParams .= ' -loop 0 ';
            $info = pathinfo($filenameOutput);
            $file = $info['filename'] . '.gif';
            $filenameOutput = $imageFolder . $file;
        } else {
            $additionalParams = ' -vcodec libx264 -pix_fmt yuv420p';
        }

        $filterComplex = '';
        if (count($cfilter) > 0) {
            $filterComplex = '-filter_complex "' . implode(',', $cfilter) . '"';
        }

        $cmd = "ffmpeg -i $filenameTmp $filterComplex $additionalParams $filenameOutput";
        exec($cmd, $output, $returnValue);

        if (!$config['picture']['keep_original']) {
            if (!unlink($filenameTmp)) {
                $imageHandler->addErrorData('Warning: Failed to remove temporary photo.');
            }
        }

        if ($returnValue != 0) {
            // Handle the exception
            if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
                $logger->error('Error: ', $imageHandler->errorLog);
            }
            $data = [
                'error' => 'Take picture command returned an error code',
                'cmd' => $cmd,
                'returnValue' => $returnValue,
                'output' => json_encode($output),
            ];
            $logger->error('Take picture command returned an error code', $data);
            echo json_encode($data);
            die();
        }

        /* TODO gallery doesn't support videos atm
        // insert into database
        if ($config['database']['enabled']) {
            $database->appendContentToDB($file);
        }*/

        // Change permissions
        $picture_permissions = $config['picture']['permissions'];
        if (!chmod($filenameOutput, (int)octdec($picture_permissions))) {
            $imageHandler->addErrorData('Warning: Failed to change picture permissions.');
        }
    }
} catch (\Exception $e) {
    // Handle the exception
    if (isset($imageResource) && $imageResource instanceof \GdImage) {
        unset($imageResource);
    }
    if (isset($imageHandler) && is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $logger->error('Error:', $imageHandler->errorLog);
    }
    $data = ['error' => $e->getMessage()];
    $logger->error($e->getMessage());
    echo json_encode($data);
    die();
}

$images = [];
$filenameOutputs = glob("$filenameOutput*");
if (is_array($filenameOutputs)) {
    foreach ($filenameOutputs as $filename) {
        $images[] = basename($filename);
    }
} else {
    $images[] = basename((string)$filenameOutputs);
}

$data = [
    'file' => $file,
    'images' => $images,
];
if ($config['dev']['loglevel'] > 1) {
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $logger->error('Error: ', $imageHandler->errorLog);
    }
}
echo json_encode($data);
exit();
