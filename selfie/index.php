<?php

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$imageHandler = new Image();

$database = DatabaseManagerService::getInstance();

$languageService = LanguageService::getInstance();
$pageTitle = 'Selfie uploader - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$error = false;
$success = false;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';

if (isset($_POST['submit'])) {
    // Process uploaded images
    $uploadedImages = $_FILES['images'];

    // Array of allowed image file types
    $allowedTypes = ImageUtility::supportedMimeTypesSelect;

    for ($i = 0; $i < count($uploadedImages['name']); $i++) {
        $imageHandler->imageModified = false;
        $imageName = $uploadedImages['name'][$i];
        $imageTmpName = $uploadedImages['tmp_name'][$i];
        $imageType = $uploadedImages['type'][$i];
        $imageNewName = Image::createNewFilename('dateformatted');

        $filename_photo = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
        $filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $image;
        $filename_thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $imageNewName;

        // Check if the file type is allowed
        if (in_array($imageType, $allowedTypes)) {
            // Move the uploaded image to the custom folder
            move_uploaded_file($imageTmpName, $filename_tmp);
            
            $imageResource = $imageHandler->createFromImage($filename_tmp);
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
            }
            $thumb_size = intval(substr($config['picture']['thumb_size'], 0, -2));
            $imageHandler->resizeMaxWidth = $thumb_size;
            $imageHandler->resizeMaxHeight = $thumb_size;
            $thumbResource = $imageHandler->resizeImage($imageResource);
            $imageHandler->jpegQuality = $config['jpeg_quality']['thumb'];
            if (!$imageHandler->saveJpeg($thumbResource, $filename_thumb)) {
                $imageHandler->addErrorData('Warning: Failed to create thumbnail.');
            }
            if ($imageHandler->imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
                $imageHandler->jpegQuality = $config['jpeg_quality']['image'];
                if (!$imageHandler->saveJpeg($imageResource, $filename_photo)) {
                    $imageHandler->addErrorData('Warning: Failed to create image.');
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
        } else {
            $error = true;
        }
    }
    if (!$error) {
        $success = true;
    }
    if (is_array($imageHandler->errorLog) && !empty($imageHandler->errorLog)) {
        $logger->error('Error', $imageHandler->errorLog);
    }
}
?>

<body>
    <div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
        <div class="w-full flex items-center justify-center flex-col">
            <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                        Selfie uploader
                    </div>
                    <div class="relative">
                        <input class="<?= $btnClass ?>" type="file" name="images[]" id="images" accept="image/*" capture="camera" required>
			<br><br>
                    </div>

                    <div class="mt-6">
                        <input class="<?= $btnClass ?>" type="submit" name="submit" value="Upload">
                    </div>
                </form>
            </div>

            <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
            </div>

            <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
<?php
echo getMenuBtn(PathUtility::getPublicPath('gallery'), 'gallery', $config['icons']['gallery']);
echo getMenuBtn(PathUtility::getPublicPath('slideshow'), 'slideshow', $config['icons']['slideshow']);
?>
                </div>
            </div>
        </div>
    </div>
