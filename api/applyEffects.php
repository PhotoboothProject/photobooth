<?php

require_once '../lib/boot.php';

use Photobooth\DataLogger;
use Photobooth\DatabaseManager;
use Photobooth\Image;
use Photobooth\ImageFilter;
use Photobooth\Helper;
use Photobooth\Collage;

header('Content-Type: application/json');

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

if (is_file(__DIR__ . '/../private/api/applyEffects.php')) {
    $Logger->addLogData(['Info' => 'Using private/api/applyEffects.php.']);
    $Logger->logToFile();
    include __DIR__ . '/../private/api/applyEffects.php';
}

try {
    $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

    if ($isCollage) {
        list($collageSrcImagePaths, $srcImages) = Collage::getCollageFiles($config['collage'], $filename_tmp, $file, $srcImages);

        if (!Collage::createCollage($collageSrcImagePaths, $filename_tmp, $image_filter)) {
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
                        ImageFilter::applyFilter($image_filter, $imageResource);
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

        // send to ftp server
        if ($config['ftp']['enabled']) {
            // init connection to ftp server
            $ftp = ftp_ssl_connect($config['ftp']['baseURL'], $config['ftp']['port']);

            // login to ftp server
            $login_result = ftp_login($ftp, $config['ftp']['username'], $config['ftp']['password']);

            if (!$login_result) {
                $Logger->logErrorAndDie("Can't connect to FTP Server!");
            }

            // turn passive mode on to enable creation of folder and upload of files
            ftp_pasv($ftp, true);

            $destination = empty($config['ftp']['baseFolder']) ? '' : DIRECTORY_SEPARATOR . $config['ftp']['baseFolder'] . DIRECTORY_SEPARATOR;

            $destination .= $config['ftp']['folder'] . DIRECTORY_SEPARATOR . Helper::slugify($config['ftp']['title']);
            if ($config['ftp']['appendDate']) {
                $destination .= DIRECTORY_SEPARATOR . date('Y/m/d');
            }

            // navigate trough folder on the server to the destination
            @Helper::cdFTPTree($ftp, $destination);

            // upload processed picture into destination folder
            $put_result = ftp_put($ftp, $image, $filename_photo, FTP_BINARY);

            if (!$put_result) {
                $Logger->logErrorAndDie('Unable to save file on FTP Server!');
            }

            // upload the thumbnail if enabled
            if ($config['ftp']['upload_thumb']) {
                $thumb_result = ftp_put($ftp, 'tmb_' . $image, $filename_thumb, FTP_BINARY);

                if (!$thumb_result) {
                    $ErrorData = [
                        'error' => 'Unable to load the thumbnail',
                    ];
                    $Logger->addLogData($ErrorData);
                }
            }

            // check if the webpage is enabled and is not already loaded on the ftp server
            if ($config['ftp']['create_webpage'] && (!isset($_SESSION['ftpWebpageLoaded']) || $_SESSION['ftpWebpageLoaded'] != $config['ftp']['title'])) {
                // if the date folder structure is appended, return to the main folder
                if ($config['ftp']['appendDate']) {
                    @Helper::cdFTPTree($ftp, '../../../');
                }

                // another security check on the file in the server (e.g. 2-day event with the same ftp folder location)
                $webpage_exist = ftp_size($ftp, 'index.php');
                if ($webpage_exist == -1) {
                    // get the index.php template file from the configured location
                    $webpage_template = file_get_contents($config['ftp']['template_location']);

                    // set the {title} variable
                    $final_webpage = str_replace('{title}', $config['ftp']['title'], $webpage_template);

                    // put the file into a stream
                    $stream = fopen('php://memory', 'r+');
                    fwrite($stream, $final_webpage);
                    rewind($stream);

                    // load the index.php result file in the ftp server
                    $upload_webpage = ftp_fput($ftp, 'index.php', $stream, FTP_BINARY);

                    fclose($stream);

                    if (!$upload_webpage) {
                        $Logger->logErrorAndDie('Unable to save file on FTP Server!');
                    }

                    // update the session variable to avoid unnecessary checks
                    $_SESSION['ftpWebpageLoaded'] = $config['ftp']['title'];
                }
            }

            // close the connection
            ftp_close($ftp);
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
