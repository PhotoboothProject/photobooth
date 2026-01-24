<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Collage;
use Photobooth\Rembg;
use Photobooth\Enum\FolderEnum;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Processor\ImageProcessor;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

checkCsrfOrFail($_POST);

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$database = DatabaseManagerService::getInstance();
$remoteStorage = RemoteStorageService::getInstance();

$processor = null;

try {
    if (empty($_POST['file'])) {
        throw new \Exception('No file provided');
    }

    $vars['fileName'] = basename((string)$_POST['file']);
    if ($vars['fileName'] === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $vars['fileName'])) {
        throw new \Exception('Invalid file name provided');
    }

    if (!isset($_POST['style']) || !in_array($_POST['style'], ['photo', 'collage', 'custom', 'chroma'])) {
        throw new \Exception('Invalid or missing style parameter');
    }

    if (isset($_POST['collageLayout'])) {
        $config['collage']['layout'] = $_POST['collageLayout'];

    }

    $limitData = Collage::calculateLimit($config['collage'], $logger);
    $config['collage']['limit'] = $limitData['limit'];
    $config['collage']['placeholder'] = $limitData['placeholderEnabled'];

    $vars['style'] = $_POST['style'];

    $vars['imageFilter'] = null;
    if (!isset($_POST['filter'])) {
        $logger->debug('No filter provided.');
    } elseif (!empty($_POST['filter'])) {
        $vars['imageFilter'] = ImageFilterEnum::tryFrom($_POST['filter']);
    }
} catch (\Exception $e) {
    // Handle the exception
    $logger->error($e->getMessage(), $_POST);
    echo json_encode(['error' => $e->getMessage()]);
    die();
}

$vars['isCollage'] = $_POST['style'] === 'collage';
$vars['editSingleCollage'] = false;
$vars['isChroma'] = $_POST['style'] === 'chroma';

$vars['srcImages'] = [];
$vars['srcImages'][] = $vars['fileName'];

$applyEffectsPath = PathUtility::getAbsolutePath('private/api/applyEffects.php');
if (is_file($applyEffectsPath)) {
    $logger->debug('Using private/api/applyEffects.php.');

    try {
        include $applyEffectsPath;
    } catch (\Exception $e) {
        $logger->error('Error (private applyEffects): ' . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
        die();
    }
}

try {
    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];

    $vars['tmpFile'] = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $vars['fileName'];

    if (class_exists('Photobooth\Processor\ImageProcessor')) {
        $processor = new ImageProcessor($imageHandler, $logger, $database, $vars, $config);
    }

    if ($vars['isCollage']) {
        [$vars['collageSrcImagePaths'], $vars['srcImages']] = Collage::getCollageFiles($config['collage'], $vars['tmpFile'], $vars['fileName'], $vars['srcImages']);

        if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'preCollageProcessing')) {
            [$imageHandler, $vars, $config] = $processor->preCollageProcessing($imageHandler, $vars, $config);
        }
        if (!Collage::createCollage($config, $vars['collageSrcImagePaths'], $vars['tmpFile'], $vars['imageFilter'])) {
            throw new \Exception('Error creating collage image.');
        }
        if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'postCollageProcessing')) {
            [$imageHandler, $vars, $config] = $processor->postCollageProcessing($imageHandler, $vars, $config);
        }
    }

    foreach ($vars['srcImages'] as $vars['singleImageFile']) {
        $imageHandler->imageModified = false;
        $vars['resultFile'] = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $vars['singleImageFile'];
        $vars['keyingFile'] = FolderEnum::KEYING->absolute() . DIRECTORY_SEPARATOR . $vars['singleImageFile'];
        $vars['tmpFile'] = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $vars['singleImageFile'];
        $vars['thumbFile'] = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $vars['singleImageFile'];

        if (!file_exists($vars['tmpFile'])) {
            throw new \Exception('Image doesn\'t exist.');
        }

        $imageResource = $imageHandler->createFromImage($vars['tmpFile']);
        if (!$imageResource) {
            throw new \Exception('Error creating image resource.');
        }

        if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'preImageProcessing')) {
            [$imageHandler, $vars, $config, $imageResource] = $processor->preImageProcessing($imageHandler, $vars, $config, $imageResource);
        }
        if (!$vars['isChroma']) {
            if ($vars['isCollage'] && $vars['fileName'] != $vars['singleImageFile']) {
                $vars['editSingleCollage'] = true;
                $imageHandler->framePath = PathUtility::getPublicPath($config['collage']['take_frame'] === 'always' ? $config['collage']['frame'] : $config['picture']['frame']);
            } else {
                $vars['editSingleCollage'] = false;
                $imageHandler->framePath = PathUtility::getPublicPath($config['picture']['frame']);
            }

            if (!$vars['isCollage'] || $vars['editSingleCollage']) {
                $filterProcessSize = intval($config['filters']['process_size'] ?? 0);

                // only downscale if filter not plain, rembg is enabled
                $originalResource = null;
                if ($vars['imageFilter'] !== ImageFilterEnum::PLAIN || $config['rembg']['enabled']) {
                    $originalWidth    = imagesx($imageResource);
                    $originalHeight   = imagesy($imageResource);
                    $originalResource = $imageResource;

                    if ($filterProcessSize > 0 && ($originalWidth > $filterProcessSize || $originalHeight > $filterProcessSize)) {
                        $downscaledResource = $imageHandler->resizeImage($imageResource, $filterProcessSize);
                        if ($downscaledResource instanceof \GdImage) {
                            $imageResource = $downscaledResource;
                        }
                    }
                }

                // apply filter (optionally downscale first for performance)
                if ($vars['imageFilter'] !== null && $vars['imageFilter'] !== ImageFilterEnum::PLAIN) {
                    try {
                        ImageUtility::applyFilter($vars['imageFilter'], $imageResource);
                        $imageHandler->imageModified = true;
                    } catch (\Exception $e) {
                        throw new \Exception('Error applying image filter.');
                    }

                }

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

                if ((int)$config['picture']['rotation'] !== 0) {
                    $imageResource = $imageHandler->rotateResizeImage(
                        image: $imageResource,
                        degrees: (int)$config['picture']['rotation'],
                    );
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error resizing resource.');
                    }
                }

                // Apply rembg
                [$imageHandler, $imageResource] = Rembg::process($imageHandler, $vars, $config['rembg'], $imageResource);

                if ($config['picture']['polaroid_effect']) {
                    $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
                    $imageResource = $imageHandler->effectPolaroid($imageResource);
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error applying polaroid effect.');
                    }
                }

                if (($config['picture']['take_frame'] && !$vars['isCollage']) || ($vars['editSingleCollage'] && ($config['collage']['take_frame'] === 'always' || $config['collage']['take_frame'] !== 'always' && $config['picture']['take_frame']))) {
                    if (!$vars['isCollage'] || $config['collage']['take_frame'] !== 'always') {
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

                // Maybe we want this later or configurable, will take some time to process upscale again
                // Upscale back to original size
                //                if (!empty($originalResource) && $originalResource !== $imageResource) {
                //                        $restored = $imageHandler->resizeImage($imageResource, $originalWidth, $originalHeight);
                //                        if ($restored instanceof \GdImage) {
                //                            if ($imageResource instanceof \GdImage) {
                //                                unset($imageResource);
                //                            }
                //                            $imageResource = $restored;
                //                        }
                //                }
            }
        }

        if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'postImageProcessing')) {
            [$imageHandler, $vars, $config, $imageResource] = $processor->postImageProcessing($imageHandler, $vars, $config, $imageResource);
        }

        if ($config['keying']['enabled'] || $vars['isChroma']) {
            $chroma_size = intval(substr($config['keying']['size'], 0, -2));
            $chromaCopyResource = $imageHandler->resizeImage($imageResource, $chroma_size);
            if ($chromaCopyResource instanceof \GdImage) {
                $imageHandler->jpegQuality = $config['jpeg_quality']['chroma'];
                if (!$imageHandler->saveJpeg($chromaCopyResource, $vars['keyingFile'])) {
                    $imageHandler->addErrorData('Warning: Failed to save chroma image copy.');
                }
            } else {
                $imageHandler->addErrorData('Warning: Failed to resize chroma resource.');
            }
            if ($chromaCopyResource instanceof \GdImage) {
                unset($chromaCopyResource);
            }
        }

        if ($config['textonpicture']['enabled'] && (!$vars['isCollage'] && !$vars['isChroma'] || $vars['editSingleCollage'])) {
            // calculate and apply text on picture if image got downscaled before
            $scale = 1.0;
            if (isset($originalWidth) && isset($originalHeight)) {
                $currentWidth = imagesx($imageResource);
                $scale        = $currentWidth / $originalWidth;
            }

            // Cast after scaling to avoid implicit float-to-int deprecation warnings in PHP 8.4
            $imageHandler->fontSize        = (int) round($config['textonpicture']['font_size'] * $scale);
            $imageHandler->textLineSpacing = (int) round($config['textonpicture']['linespace'] * $scale);
            $imageHandler->fontLocationX   = (int) round($config['textonpicture']['locationx'] * $scale);
            $imageHandler->fontLocationY   = (int) round($config['textonpicture']['locationy'] * $scale);
            $imageHandler->fontRotation = $config['textonpicture']['rotation'];
            $imageHandler->fontColor = $config['textonpicture']['font_color'];
            $imageHandler->fontPath = $config['textonpicture']['font'];
            $imageHandler->textLine1 = $config['textonpicture']['line1'];
            $imageHandler->textLine2 = $config['textonpicture']['line2'];
            $imageHandler->textLine3 = $config['textonpicture']['line3'];
            $imageResource = $imageHandler->applyText($imageResource);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error applying text to image resource.');
            }
        }

        // image scale, create thumbnail
        $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
        $thumbResource = $imageHandler->resizeImage($imageResource, $thumb_size);
        if ($thumbResource instanceof \GdImage) {
            $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
            if (!$imageHandler->saveJpeg($thumbResource, $vars['thumbFile'])) {
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
            if (!$imageHandler->saveJpeg($imageResource, $vars['resultFile'])) {
                throw new \Exception('Failed to save image.');
            }
            // preserve jpeg meta data
            if ($config['picture']['preserve_exif_data'] && $config['commands']['exiftool']) {
                try {
                    $cmd = sprintf($config['commands']['exiftool'], $vars['tmpFile'], $vars['resultFile']);
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
            if (!copy($vars['tmpFile'], $vars['resultFile'])) {
                throw new \Exception('Failed to copy photo.');
            }
        }
        unset($imageResource);

        // insert into database
        if ($config['database']['enabled']) {
            if (($vars['isChroma'] && $config['keying']['show_all'] === true) || !$vars['isChroma']) {
                $database->appendContentToDB($vars['singleImageFile']);
            }
        }

        // Store images on remote storage
        if ($config['ftp']['enabled']) {
            $remoteStorage->write($remoteStorage->getStorageFolder() . '/images/' . $vars['singleImageFile'], (string) file_get_contents($vars['resultFile']));
            $remoteStorage->write($remoteStorage->getStorageFolder() . '/thumbs/' . $vars['singleImageFile'], (string) file_get_contents($vars['thumbFile']));
            if ($config['ftp']['create_webpage']) {
                $remoteStorage->createWebpage();
            }
        }

        // Change permissions
        $picture_permissions = $config['picture']['permissions'];
        if (!chmod($vars['resultFile'], (int)octdec($picture_permissions))) {
            $imageHandler->addErrorData('Warning: Failed to change picture permissions.');
        }

        if (!$config['picture']['keep_original']) {
            if (!unlink($vars['tmpFile'])) {
                $imageHandler->addErrorData('Warning: Failed to remove temporary photo.');
            }
        }

        if ($_POST['style'] === 'chroma' && $config['keying']['show_all'] === false) {
            if (!unlink($vars['resultFile'])) {
                $imageHandler->addErrorData('Warning: Failed to remove photo.');
            }
            if (!unlink($vars['thumbFile'])) {
                $imageHandler->addErrorData('Warning: Failed to remove thumbnail.');
            }
        }
    }
} catch (\Exception $e) {
    // Handle the exception
    if (isset($imageResource) && $imageResource instanceof \GdImage) {
        unset($imageResource);
    }
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
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
    'file' => $vars['fileName'],
    'images' => $vars['srcImages'],
];
$logger->debug('effects applied', $data);
echo json_encode($data);
exit();
