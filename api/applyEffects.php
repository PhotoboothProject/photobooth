<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\ImageUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$database = DatabaseManagerService::getInstance();
$remoteStorage = RemoteStorageService::getInstance();

try {
    if (empty($_POST['file'])) {
        throw new \Exception('No file provided');
    }

    $file = $_POST['file'];

    if (!isset($_POST['style']) || !in_array($_POST['style'], ['photo', 'collage', 'custom', 'chroma'])) {
        throw new \Exception('Invalid or missing style parameter');
    }

    $style = $_POST['style'];

    $filter = null;
    if (!isset($_POST['filter'])) {
        $logger->debug('No filter provided.');
    } elseif (!empty($_POST['filter'])) {
        $filter = ImageFilterEnum::tryFrom($_POST['filter']);
    }
} catch (\Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

$isCollage = false;
$isChroma = false;
if ($_POST['style'] === 'collage') {
    $isCollage = true;
} elseif ($_POST['style'] === 'chroma') {
    $isChroma = true;
}

$srcImages = [];
$srcImages[] = $file;

if (is_file(__DIR__ . '/../private/api/applyEffects.php')) {
    $logger->debug('Using private/api/applyEffects.php.');
    include __DIR__ . '/../private/api/applyEffects.php';
}

try {
    $filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $file;

    if ($isCollage) {
        list($collageSrcImagePaths, $srcImages) = Collage::getCollageFiles($config['collage'], $filename_tmp, $file, $srcImages);

        if (!Collage::createCollage($config, $collageSrcImagePaths, $filename_tmp, $filter)) {
            throw new \Exception('Error creating collage image.');
        }
    }

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];

    foreach ($srcImages as $image) {
        $imageHandler->imageModified = false;
        $filename_photo = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $image;
        $filename_keying = FolderEnum::KEYING->absolute() . DIRECTORY_SEPARATOR . $image;
        $filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $image;
        $filename_thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $image;

        if (!file_exists($filename_tmp)) {
            throw new \Exception('Image doesn\'t exist.');
        }

        $imageResource = $imageHandler->createFromImage($filename_tmp);
        if (!$imageResource) {
            throw new \Exception('Error creating image resource.');
        }

        if (!$isChroma) {
            if ($isCollage && $file != $image) {
                $editSingleCollage = true;
                $imageHandler->framePath = $config['collage']['frame'];
            } else {
                $editSingleCollage = false;
                $imageHandler->framePath = $config['picture']['frame'];
            }

            if (!$isCollage || $editSingleCollage) {
                if ($config['picture']['flip'] !== 'off') {
                    try {
                        if ($config['picture']['flip'] === 'flip-horizontal') {
                            imageflip($imageResource, IMG_FLIP_HORIZONTAL);
                        } elseif ($config['picture']['flip'] === 'flip-vertical') {
                            imageflip($imageResource, IMG_FLIP_VERTICAL);
                        } elseif ($config['picture']['flip'] === 'flip-both') {
                            imageflip($imageResource, IMG_FLIP_BOTH);
                        }
                        $imageHandler->imageModified = true;
                    } catch (\Exception $e) {
                        throw new \Exception('Error flipping image.');
                    }
                }

                // apply filter
                if ($filter !== null && $filter !== ImageFilterEnum::PLAIN) {
                    try {
                        ImageUtility::applyFilter($filter, $imageResource);
                        $imageHandler->imageModified = true;
                    } catch (\Exception $e) {
                        throw new \Exception('Error applying image filter.');
                    }
                }

                if ($config['picture']['polaroid_effect'] && !$isCollage) {
                    $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
                    $imageResource = $imageHandler->effectPolaroid($imageResource);
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error applying polaroid effect.');
                    }
                }

                if (($config['picture']['take_frame'] && !$isCollage) || ($editSingleCollage && $config['collage']['take_frame'] === 'always')) {
                    if (!$isCollage) {
                        $imageHandler->frameExtend = $config['picture']['extend_by_frame'];
                        if ($config['picture']['extend_by_frame']) {
                            $imageHandler->frameExtendLeft = $config['picture']['frame_left_percentage'];
                            $imageHandler->frameExtendRight = $config['picture']['frame_right_percentage'];
                            $imageHandler->frameExtendBottom = $config['picture']['frame_bottom_percentage'];
                            $imageHandler->frameExtendTop = $config['picture']['frame_top_percentage'];
                        }
                    } else {
                        $imageHandler->frameExtend = false;
                    }
                    $imageResource = $imageHandler->applyFrame($imageResource);
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error applying frame to image resource.');
                    }
                }

                if ($config['picture']['rotation'] !== '0') {
                    $imageHandler->resizeRotation = $config['picture']['rotation'];
                    $imageResource = $imageHandler->rotateResizeImage($imageResource);
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error resizing resource.');
                    }
                }
            }
        }
        if ($config['keying']['enabled'] || $isChroma) {
            $chroma_size = intval(substr($config['keying']['size'], 0, -2));
            $imageHandler->resizeMaxWidth = $chroma_size;
            $imageHandler->resizeMaxHeight = $chroma_size;
            $chromaCopyResource = $imageHandler->resizeImage($imageResource);
            if ($chromaCopyResource instanceof \GdImage) {
                $imageHandler->jpegQuality = $config['jpeg_quality']['chroma'];
                if (!$imageHandler->saveJpeg($chromaCopyResource, $filename_keying)) {
                    $imageHandler->addErrorData('Warning: Failed to save chroma image copy.');
                }
            } else {
                $imageHandler->addErrorData('Warning: Failed to resize chroma resource.');
            }
            if ($chromaCopyResource instanceof \GdImage) {
                unset($chromaCopyResource);
            }
        }

        if (!$isCollage && !$isChroma && $config['textonpicture']['enabled']) {
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

        // image scale, create thumbnail
        $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
        $imageHandler->resizeMaxWidth = $thumb_size;
        $imageHandler->resizeMaxHeight = $thumb_size;
        $thumbResource = $imageHandler->resizeImage($imageResource);
        if ($thumbResource instanceof \GdImage) {
            $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
            if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
                $imageHandler->addErrorData('Warning: Failed to create thumbnail.');
            }
        } else {
            $imageHandler->addErrorData('Warning: Failed to resize thumbnail.');
        }

        if ($thumbResource instanceof \GdImage) {
            unset($thumbResource);
        }

        $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
        if ($imageHandler->imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
            if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
                throw new \Exception('Failed to save image.');
            }
            // preserve jpeg meta data
            if ($config['picture']['preserve_exif_data'] && $config['commands']['exiftool']) {
                try {
                    $cmd = sprintf($config['commands']['exiftool'], $filename_tmp, $filename_photo);
                    $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

                    exec($cmd, $output, $returnValue);

                    if ($returnValue) {
                        $errorData = [
                            'error' => 'exiftool returned with an error code',
                            'cmd' => $cmd,
                            'returnValue' => $returnValue,
                            'output' => $output,
                        ];
                        $logger->error('exiftool returned with an error code', $errorData);
                    }
                } catch (\Exception $e) {
                    $logger->error($e->getMessage());
                }
            }
        } else {
            if (!copy($filename_tmp, $filename_photo)) {
                throw new \Exception('Failed to copy photo.');
            }
        }
        unset($imageResource);

        // insert into database
        if ($config['database']['enabled']) {
            if (($isChroma && $config['keying']['show_all'] === true) || !$isChroma) {
                $database->appendContentToDB($image);
            }
        }

        // Store images on remote storage
        if ($config['ftp']['enabled']) {
            $remoteStorage->write($remoteStorage->getStorageFolder() . '/images/' . $image, (string) file_get_contents($filename_photo));
            $remoteStorage->write($remoteStorage->getStorageFolder() . '/thumbs/' . $image, (string) file_get_contents($filename_thumb));
            if ($config['ftp']['create_webpage']) {
                $remoteStorage->createWebpage();
            }
        }

        // Change permissions
        $picture_permissions = $config['picture']['permissions'];
        if (!chmod($filename_photo, (int)octdec($picture_permissions))) {
            $imageHandler->addErrorData('Warning: Failed to change picture permissions.');
        }

        if (!$config['picture']['keep_original']) {
            if (!unlink($filename_tmp)) {
                $imageHandler->addErrorData('Warning: Failed to remove temporary photo.');
            }
        }

        if ($_POST['style'] === 'chroma' && $config['keying']['show_all'] === false) {
            if (!unlink($filename_photo)) {
                $imageHandler->addErrorData('Warning: Failed to remove photo.');
            }
            if (!unlink($filename_thumb)) {
                $imageHandler->addErrorData('Warning: Failed to remove thumbnail.');
            }
        }
    }
} catch (\Exception $e) {
    // Handle the exception
    if (isset($imageResource) && $imageResource instanceof \GdImage) {
        unset($imageResource);
    }
    if (isset($imageHandler) && is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $logger->error('Error', $imageHandler->errorLog);
    }
    $logger->error($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
    $logger->error('Error', $imageHandler->errorLog);
}

$data = [
    'file' => $file,
    'images' => $srcImages,
];
$logger->debug('effects applied', $data);
echo json_encode($data);
exit();
