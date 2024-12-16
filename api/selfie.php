<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\FileUploader;
use Photobooth\Image;
use Photobooth\Enum\FolderEnum;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$imageHandler = new Image();

$database = DatabaseManagerService::getInstance();

if (isset($_FILES['images'])) {
    $folderName = 'data/tmp';
    $uploadedFiles = $_FILES['images'];

    $uploader = new FileUploader($folderName, $uploadedFiles, $logger);
    $response = $uploader->uploadFiles();
    list($success, $message, $errors, $uploadedFiles, $failedFiles) = [
        $response['success'],
        $response['message'],
        $response['errors'],
        $response['uploadedFiles'],
        $response['failedFiles']
    ];

    try {
        if (count($errors) > 0) {
            throw new \Exception('Failed to upload selfie.');
        }

        foreach ($uploadedFiles as $imageName) {
            $tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $imageName;
            $imageNewName = Image::createNewFilename($config['picture']['naming']);
            $filename_photo = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $filename_thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $imageHandler->imageModified = false;

            if (!file_exists($tmp)) {
                throw new \Exception('Image doesn\'t exist:' . $tmp);
            }

            if (rename($tmp, $filename_tmp)) {
                throw new \Exception('Failed to rename image!');
            }

            $imageResource = $imageHandler->createFromImage($filename_tmp);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Error creating image resource.');
            }

            $exif = exif_read_data($filename_tmp);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:  //180°
                        $imageResource = imagerotate($imageResource, 180, 0);
                        $imageHandler->imageModified = true;
                        break;
                    case 6:  //-90°
                        $imageResource = imagerotate($imageResource, -90, 0);
                        $imageHandler->imageModified = true;
                        break;
                    case 8:  //+90°
                        $imageResource = imagerotate($imageResource, 90, 0);
                        $imageHandler->imageModified = true;
                        break;
                }
                if (!$imageResource instanceof \GdImage) {
                    throw new \Exception('Error rotating image resource.');
                }
            }
            $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
            $thumbResource = $imageHandler->resizeImage($imageResource, $thumb_size);
            if (!$thumbResource instanceof \GdImage) {
                throw new \Exception('Error creating thumb resource.');
            }
            $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
            if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
                $imageHandler->addErrorData('Warning: Failed to create thumbnail.');
            }
            if ($imageHandler->imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
                $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
                if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
                    throw new \Exception('Failed to create image.');
                }
            } else {
                if (!copy($filename_tmp, $filename_photo)) {
                    throw new \Exception('Failed to copy photo.');
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
            if ($thumbResource instanceof \GdImage) {
                unset($thumbResource);
            }
            if ($imageResource instanceof \GdImage) {
                unset($imageResource);
            }
            if ($config['database']['enabled']) {
                $database->appendContentToDB($imageNewName);
            }
        }
    } catch (\Exception $e) {
        // Handle the exception
        $logger->error($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        die();
    }
    echo json_encode([
            'success' => true,
            'message' => 'File(s) successfully uploaded and proceeded.'
        ]);
    exit();
}
