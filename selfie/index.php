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

$errors = [];
$success = false;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
$max_file_size = ini_get('upload_max_filesize');

function return_bytes($val)
{
    if (empty($val)) {
        $val = 0;
    }
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = floatval($val);
    switch ($last) {
        case 'g':
            $val *= (1024 * 1024 * 1024); //1073741824
            break;
        case 'm':
            $val *= (1024 * 1024); //1048576
            break;
        case 'k':
            $val *= 1024;
            break;
    }
    return $val;
}

//if (isset($_POST['submit'])) {
if (isset($_FILES['images'])) {
    // Process uploaded images
    $uploadedImages = $_FILES['images'];

    // Array of allowed image file types
    $allowedTypes = ImageUtility::supportedMimeTypesSelect;

    for ($i = 0; $i < count($uploadedImages['name']); $i++) {
        $fileError = $uploadedImages['error'][$i];
        $imageName = $uploadedImages['name'][$i];
        if ($fileError == 0) {
            $imageTmpName = $uploadedImages['tmp_name'][$i];
            $imageType = $uploadedImages['type'][$i];
            $imageNewName = Image::createNewFilename('dateformatted');

            $filename_photo = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $filename_tmp = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $filename_thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $imageNewName;
            $imageHandler->imageModified = false;

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
            } else {
                $errors[$imageTmpName] = $languageService->translate('upload_wrong_type');
                $logger->debug($languageService->translate('upload_wrong_type'), [$imageTmpName]);
                break;
            }
        } else {
            $errMsg = $languageService->translate(FileUtility::getErrorMessage($fileError));
            $errors[$imageTmpName] = $errMsg;
            $logger->debug($errMsg, [$imageTmpName]);
        }
    }
    if (count($errors) === 0) {
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
                        <label for="images" class="<?= $btnClass ?>" style="padding-bottom: 10%;padding-top: 10%;">TAKE A PICTURE</label>
                        <input class="<?= $btnClass ?>" style="display: none" type="file" name="images[]" id="images" accept="image/*" capture="camera" required onchange="loadFile(event)"> <!-- */ -->
                        <br></br>
                        <center>
                            <img style="max-height: 18em" id="output"/>
                        </center>
                    </div>
                    <div class='my-2'>
                        <warn></warn>
                    </div>
                    <div class="mt-6">
                        <input class="<?= $btnClass ?>" type="submit" name="submit" id="Upload" value="Upload">
                    </div>
                </form>
            </div>

            <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
            </div>
            <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4 px-4 ">
                   <?php
                      echo getMenuBtn(PathUtility::getPublicPath('gallery'), 'gallery', $config['icons']['gallery']);
echo getMenuBtn(PathUtility::getPublicPath('slideshow'), 'slideshow', $config['icons']['slideshow']);
?>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
    document.getElementById('Upload').style.visibility = 'hidden';
    var loadFile = function(event) {
        document.getElementById('Upload').style.visibility = 'visible';
        var output = document.getElementById('output');
        output.src = URL.createObjectURL(event.target.files[0]);
        var imagesize = event.target.files[0].size;
        var maxfilesize = <?= return_bytes($max_file_size); ?>;
        if (parseInt(maxfilesize) <= parseInt(imagesize)) {
            var js_Str_warn = '<?php echo $languageService->translate('file_upload_max_size') . $max_file_size; ?>';
            document.querySelector('warn').textContent = js_Str_warn;
            document.getElementById('Upload').style.visibility = 'hidden';
        }
        output.onload = function() {
            URL.revokeObjectURL(output.src) // free memory
        }
    }
</script>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');

if ($success) {
    echo '<script>setTimeout(function(){openToast("' . $languageService->translate('upload_success') . '")},500);</script>';
}
if (count($errors) > 0) {
    echo '<script>setTimeout(function(){openToast("' . $languageService->translate('upload_error') . '", "isError", 5000)},500);</script>';
}

include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
?>
