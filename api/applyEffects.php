<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Helper;
use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\ImageUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$database = DatabaseManagerService::getInstance();

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

$isCollage = $_POST['style'] === 'collage';
$editSingleCollage = false;
$isChroma = $_POST['style'] === 'chroma';

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
                $imageHandler->framePath = $config['collage']['take_frame'] === 'always' ? $config['collage']['frame'] : $config['picture']['frame'];
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

                if ($config['picture']['polaroid_effect']) {
                    $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
                    $imageResource = $imageHandler->effectPolaroid($imageResource);
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error applying polaroid effect.');
                    }
                }

                if (($config['picture']['take_frame'] && !$isCollage) || ($editSingleCollage && ($config['collage']['take_frame'] === 'always' || $config['collage']['take_frame'] !== 'always' && $config['picture']['take_frame']))) {
                    if (!$isCollage || $config['collage']['take_frame'] !== 'always') {
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
                    $imageResource = $imageHandler->rotateResizeImage(
                        image: $imageResource,
                        degrees: $config['picture']['rotation']
                    );
                    if (!$imageResource instanceof \GdImage) {
                        throw new \Exception('Error resizing resource.');
                    }
                }
            }
        }

        if ($config['keying']['enabled'] || $isChroma) {
            $chroma_size = intval(substr($config['keying']['size'], 0, -2));
            $chromaCopyResource = $imageHandler->resizeImage($imageResource, $chroma_size);
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

        if ($config['textonpicture']['enabled'] && (!$isCollage && !$isChroma || $editSingleCollage)) {
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
        $thumbResource = $imageHandler->resizeImage($imageResource, $thumb_size);
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

        // send to ftp server
        if ($config['ftp']['enabled']) {
            // init connection to ftp server
            $ftp = ftp_ssl_connect($config['ftp']['baseURL'], $config['ftp']['port']);

            if ($ftp === false) {
                $message = 'Failed to connect to FTP Server!';
                $logger->error($message, $config['ftp']);
                echo json_encode(['error' => $message]);
                die();
            }
            ftp_set_option($ftp, FTP_TIMEOUT_SEC, 10);

            // login to ftp server
            $login_result = ftp_login($ftp, $config['ftp']['username'], $config['ftp']['password']);

            if (!$login_result) {
                $message = 'Can\'t connect to FTP Server!';
                $logger->error($message, $config['ftp']);
                echo json_encode(['error' => $message]);
                die();
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
            $put_result = @ftp_put($ftp, $image, $filename_photo, FTP_BINARY);

            if (!$put_result) {
                $message = 'Unable to save file on FTP Server!';
                $logger->error($message, $config['ftp']);
                echo json_encode(['error' => $message]);
                die();
            }

            // upload the thumbnail if enabled
            if ($config['ftp']['upload_thumb']) {
                $thumb_result = ftp_put($ftp, 'tmb_' . $image, $filename_thumb, FTP_BINARY);
                if (!$thumb_result) {
                    $logger->error('Unable to load the thumbnail', $config['ftp']);
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

                    if ($webpage_template === false) {
                        throw new \Exception('File could not be read: ' . $config['ftp']['template_location']);
                    }
                    // set the {title} variable
                    $final_webpage = str_replace('{title}', $config['ftp']['title'], $webpage_template);

                    // put the file into a stream
                    $stream = fopen('php://memory', 'r+');
                    if ($stream === false) {
                        throw new \Exception('Could not put the file into a stream!');
                    }
                    fwrite($stream, $final_webpage);
                    rewind($stream);

                    // load the index.php result file in the ftp server
                    $upload_webpage = ftp_fput($ftp, 'index.php', $stream, FTP_BINARY);

                    fclose($stream);

                    if (!$upload_webpage) {
                        $message = 'Unable to save file on FTP Server!';
                        $logger->error($message, $config['ftp']);
                        echo json_encode(['error' => $message]);
                        die();
                    }

                    // update the session variable to avoid unnecessary checks
                    $_SESSION['ftpWebpageLoaded'] = $config['ftp']['title'];
                }
            }

            // close the connection
            @ftp_close($ftp);
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
