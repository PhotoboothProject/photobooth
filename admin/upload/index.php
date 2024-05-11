<?php

require_once '../../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\FileUtility;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

require_once PathUtility::getAbsolutePath('lib/configsetup.inc.php');

$languageService = LanguageService::getInstance();
$pageTitle = 'Image uploader - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$error = false;
$success = false;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';

if (isset($_POST['submit'])) {
    $folderName = $_POST['folder_name'];
    $folderPath = $config['foldersAbs']['private'] . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $folderName;
    FileUtility::createDirectory($folderPath);

    if (is_writable($folderPath)) {
        // Process uploaded images
        $uploadedImages = $_FILES['images'];

        // Array of allowed image file types
        $allowedTypes = ImageUtility::supportedMimeTypesSelect;

        for ($i = 0; $i < count($uploadedImages['name']); $i++) {
            $imageName = $uploadedImages['name'][$i];
            $imageTmpName = $uploadedImages['tmp_name'][$i];
            $imageType = $uploadedImages['type'][$i];
            $imagePath = $folderPath . '/' . $imageName;

            // Check if the file type is allowed
            if (in_array($imageType, $allowedTypes)) {
                // Move the uploaded image to the custom folder
                move_uploaded_file($imageTmpName, $imagePath);
                chmod($imagePath, 0644);
            } else {
                $error = true;
            }
        }
        if (!$error) {
            $success = true;
        }
    } else {
        $error = true;
    }
}
?>

<body>
    <div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
        <div class="w-full flex items-center justify-center flex-col">
            <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                    <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                        Image uploader
                    </div>

                    <div class="relative">
                        <label class="<?= $labelClass ?>" for="folder_name"><?=$languageService->translate('upload_folder')?></label>
                        <input class="<?= $inputClass ?>" type="text" name="folder_name" id="folder_name" required><br><br>
                        <label class="<?= $labelClass ?>" for="images"><?=$languageService->translate('upload_selection')?></label>
                        <input class="<?= $labelClass ?>" type="file" name="images[]" id="images" multiple accept="image/*" required><br><br>
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
                    echo getMenuBtn(PathUtility::getPublicPath('admin'), 'admin_panel', $config['icons']['admin']);

if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    echo getMenuBtn(PathUtility::getPublicPath('login/logout.php'), 'logout', $config['icons']['logout']);
}
?>
                </div>
            </div>
        </div>
    </div>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');

if ($success) {
    echo '<script>openToast("' . $languageService->translate('upload_success') . '");</script>';
}
if ($error !== false) {
    echo '<script>openToast("' . $languageService->translate('upload_error') . '", "isError", 5000);</script>';
}

include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
