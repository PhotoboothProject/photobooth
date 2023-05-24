<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/collage.php';
require_once '../lib/applyEffects.php';
require_once '../lib/image.php';
require_once '../lib/log.php';
require_once '../lib/nextcloud.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);
$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

try {
    if (!extension_loaded('gd')) {
        throw new Exception('GD library not loaded! Please enable GD!');
    }

    if (empty($_POST['file'])) {
        throw new Exception('No file provided');
    }

    $file = $_POST['file'];

    $database = new DatabaseManager();
    $database->db_file = DB_FILE;
    $database->file_dir = IMG_DIR;

    if (!isset($_POST['style']) || !in_array($_POST['style'], ['photo', 'collage', 'custom', 'chroma'])) {
        throw new Exception('Invalid or missing style parameter');
    }

    $style = $_POST['style'];

    $image_filter = false;

    if (!isset($_POST['filter'])) {
        $Logger->addLogData(['Warning' => 'No filter provided.']);
    } elseif (!empty($_POST['filter']) && $_POST['filter'] !== 'plain') {
        $image_filter = $_POST['filter'];
    }
} catch (Exception $e) {
    // Handle the exception
    $ErrorData = [
        'error' => $e->getMessage(),
    ];
    $Logger->addLogData($ErrorData);
    $Logger->logToFile();

    $ErrorString = json_encode($ErrorData);
    die($ErrorString);
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

try {
    $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

    if ($isCollage) {
        list($collageSrcImagePaths, $srcImages) = getCollageFiles($config['collage'], $filename_tmp, $file, $srcImages);

        if (!createCollage($collageSrcImagePaths, $filename_tmp, $image_filter)) {
            throw new Exception('Error creating collage image.');
        }
    }

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];

    foreach ($srcImages as $image) {
        $imageHandler->imageModified = false;
        $filename_photo = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $image;
        $filename_keying = $config['foldersAbs']['keying'] . DIRECTORY_SEPARATOR . $image;
        $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $image;
        $filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $image;

        if (!file_exists($filename_tmp)) {
            throw new Exception('Image doesn\'t exist.');
        }

        $imageResource = $imageHandler->createFromImage($filename_tmp);
        if (!$imageResource) {
            throw new Exception('Error creating image resource.');
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
                        if ($config['picture']['flip'] === 'horizontal') {
                            imageflip($imageResource, IMG_FLIP_HORIZONTAL);
                        } elseif ($config['picture']['flip'] === 'vertical') {
                            imageflip($imageResource, IMG_FLIP_VERTICAL);
                        } elseif ($config['picture']['flip'] === 'both') {
                            imageflip($imageResource, IMG_FLIP_BOTH);
                        }
                        $imageHandler->imageModified = true;
                    } catch (Exception $e) {
                        throw new Exception('Error flipping image.');
                    }
                }

                // apply filter
                if ($image_filter) {
                    try {
                        applyFilter($image_filter, $imageResource);
                        $imageHandler->imageModified = true;
                    } catch (Exception $e) {
                        throw new Exception('Error applying image filter.');
                    }
                }

                if ($config['picture']['polaroid_effect'] && !$isCollage) {
                    $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
                    $imageResource = $imageHandler->effectPolaroid($imageResource);
                    if (!$imageResource) {
                        throw new Exception('Error applying polaroid effect.');
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
                    if (!$imageResource) {
                        throw new Exception('Error applying frame to image resource.');
                    }
                }

                if ($config['picture']['rotation'] !== '0') {
                    $imageHandler->resizeRotation = $config['picture']['rotation'];
                    $imageResource = $imageHandler->rotateResizeImage($imageResource);
                    if (!$imageResource) {
                        throw new Exception('Error resizing resource.');
                    }
                }
            }
        }
        if ($config['keying']['enabled'] || $isChroma) {
            $chroma_size = substr($config['keying']['size'], 0, -2);
            $imageHandler->resizeMaxWidth = $chroma_size;
            $imageHandler->resizeMaxHeight = $chroma_size;
            $chromaCopyResource = $imageHandler->resizeImage($imageResource);
            $imageHandler->jpegQuality = $config['jpeg_quality']['chroma'];
            if (!$imageHandler->saveJpeg($chromaCopyResource, $filename_keying)) {
                $imageHandler->addErrorData(['Warning' => 'Failed to save chroma image copy.']);
            }
            if (is_resource($chromaCopyResource)) {
                imagedestroy($chromaCopyResource);
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
            if (!$imageResource) {
                throw new Exception('Error applying text to image resource.');
            }
        }

        // image scale, create thumbnail
        $thumb_size = substr($config['picture']['thumb_size'], 0, -2);
        $imageHandler->resizeMaxWidth = $thumb_size;
        $imageHandler->resizeMaxHeight = $thumb_size;
        $thumbResource = $imageHandler->resizeImage($imageResource);

        $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
        if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
            $imageHandler->addErrorData(['Warning' => 'Failed to create thumbnail.']);
        }
        if (is_resource($thumbResource)) {
            imagedestroy($thumbResource);
        }

        $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
        if ($imageHandler->imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
            if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
                throw new Exception('Failed to save image.');
            }
            // preserve jpeg meta data
            if ($config['picture']['preserve_exif_data'] && $config['exiftool']['cmd']) {
                try {
                    $cmd = sprintf($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
                    $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

                    exec($cmd, $output, $returnValue);

                    if ($returnValue) {
                        $ErrorData = [
                            'error' => 'exiftool returned with an error code',
                            'cmd' => $cmd,
                            'returnValue' => $returnValue,
                            'output' => $output,
                        ];
                        $Logger->addLogData($ErrorData);
                    }
                } catch (Exception $e) {
                    $ErrorData = [
                        'error' => $e->getMessage(),
                    ];
                    $Logger->addLogData($ErrorData);
                }
            }
        } else {
            if (!copy($filename_tmp, $filename_photo)) {
                throw new Exception('Failed to copy photo.');
            }
        }
        imagedestroy($imageResource);

        // insert into database
        if ($config['database']['enabled']) {
            if (!$isChroma || ($isChroma && $config['keying']['show_all'] === true)) {
                $database->appendContentToDB($image);
            }
        }

        // Change permissions
        $picture_permissions = $config['picture']['permissions'];
        if (!chmod($filename_photo, octdec($picture_permissions))) {
            $imageHandler->addErrorData(['Warning' => 'Failed to change picture permissions.']);
        }

        if (!$config['picture']['keep_original']) {
            if (!unlink($filename_tmp)) {
                $imageHandler->addErrorData(['Warning' => 'Failed to remove temporary photo.']);
            }
        }

        if ($_POST['style'] === 'chroma' && $config['keying']['show_all'] === false) {
            if (!unlink($filename_photo)) {
                $imageHandler->addErrorData(['Warning' => 'Failed to remove photo.']);
            }
            if (!unlink($filename_thumb)) {
                $imageHandler->addErrorData(['Warning' => 'Failed to remove thumbnail.']);
            }
        }

        // Check for Nextcloud Enabled and Upload image to Nextcloud
        if ($config['nextcloud']['enabled'] && !$config['nextcloud']['mntEnabled']) {
            $nextcloud = new Nextcloud($config['nextcloud'], $Logger);
            $nextcloud->uploadImage($config['foldersAbs']['images'], $file);
        }
    }
} catch (Exception $e) {
    // Handle the exception
    if (isset($imageResource) && is_resource($imageResource)) {
        imagedestroy($imageResource);
    }
    if (isset($imageHandler) && is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $Logger->addLogData($imageHandler->errorLog);
    }
    $ErrorData = [
        'error' => $e->getMessage(),
    ];
    $Logger->addLogData($ErrorData);
    $Logger->logToFile();

    $ErrorString = json_encode($ErrorData);
    die($ErrorString);
}

$LogData = [
    'file' => $file,
    'images' => $srcImages,
];
if ($config['dev']['loglevel'] > 1) {
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $Logger->addLogData($imageHandler->errorLog);
    }
    $Logger->addLogData($LogData);
    $Logger->logToFile();
}

$LogString = json_encode($LogData);
echo $LogString;
exit();
