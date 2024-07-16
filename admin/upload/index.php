<?php

require_once '../../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\FontUtility;
use Photobooth\Utility\FileUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Service\LoggerService;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$languageService = LanguageService::getInstance();
$pageTitle = 'File uploader - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$errors = [];
$success = false;
$folderName = null;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mb-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
$max_file_size = ini_get('upload_max_filesize');

if (isset($_POST['submit'])) {
    $folderName = $_POST['folder_name'];
    $uploadedFiles = $_FILES['files'];

    // in future this array could be expanded
    $typeChecker = [
        'images/background' => ImageUtility::supportedMimeTypesSelect,
        'images/frames' => ImageUtility::supportedMimeTypesSelect,
        'images/logo' => ImageUtility::supportedMimeTypesSelect,
        'images/placeholder' => ImageUtility::supportedMimeTypesSelect,
        'fonts' => FontUtility::supportedMimeTypesSelect,
    ];

    $logger->debug('folderName', [$folderName]);

    // check if folder is supported
    if(!isset($typeChecker[$folderName])) {
        $errors[$folderName] = $languageService->translate('upload_folder_invalid');
        $logger->debug($languageService->translate('upload_folder_invalid'), [$folderName]);
    } else {
        $folderPath = PathUtility::getAbsolutePath('private/' . $folderName);
        // check if folder is writeable
        if (!is_writable($folderPath)) {
            $errors[$folderName] = $languageService->translate('upload_unable_to_write_folder');
            $logger->debug($languageService->translate('upload_unable_to_write_folder'), [$folderPath]);
        }
    }

    if (count($errors) === 0) {
        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
            $fileError = $uploadedFiles['error'][$i];
            $fileName = $uploadedFiles['name'][$i];
            if($fileError == 0) {
                $fileTmpName = $uploadedFiles['tmp_name'][$i];
                $fileType = $uploadedFiles['type'][$i];
                $fileSize = $uploadedFiles['size'][$i];
                $filePath = $folderPath . '/' . $fileName;
                $logger->debug('fileName', [$fileName]);

                // check if filetype is allowed for this folder
                if(!in_array($fileType, $typeChecker[$folderName])) {
                    $errors[$fileName] = $languageService->translate('upload_wrong_type');
                    $logger->debug($languageService->translate('upload_wrong_type'), [$fileName]);
                    break;
                }

                // check if file already exists
                if(file_exists($filePath)) {
                    $errors[$fileName] = $languageService->translate('upload_file_already_exists');
                    $logger->debug($languageService->translate('upload_file_already_exists'), [$fileName]);
                    break;
                }

                move_uploaded_file($fileTmpName, $filePath);
                chmod($filePath, 0644);
            } else {
                $errMsg = $languageService->translate(FileUtility::getErrorMessage($fileError));
                $errors[$fileName] = $errMsg;
                $logger->debug($errMsg, [$fileName]);
            }
        }
    }

    if(count($errors) === 0) {
        $success = true;
    }
}
?>

<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                    File uploader
                </div>

                <div class="relative">
                    <label class="<?= $labelClass ?>" for="folder_name"><?=$languageService->translate('upload_folder')?></label>
                    <select class="<?= $inputClass ?>" name="folder_name">
                        <optgroup label="images/">
                            <option value="images/background" <?= $folderName === 'images/background' ? 'selected' : '' ?>>background</option>
                            <option value="images/frames" <?= $folderName === 'images/frames' ? 'selected' : '' ?>>frames</option>
                            <option value="images/logo" <?= $folderName === 'images/logo' ? 'selected' : '' ?>>logo</option>
                            <option value="images/placeholder" <?= $folderName === 'images/placeholder' ? 'selected' : '' ?>>placeholder</option>
                        </optgroup>
                        <option value="fonts" <?= $folderName === 'fonts' ? 'selected' : '' ?>>fonts</option>
                    </select>
                    <label class="<?= $labelClass ?>" for="files"><?=$languageService->translate('upload_selection')?></label>
                    <input class="<?= $labelClass ?>" type="file" name="files[]" id="files" multiple accept="image/*, .ttf" required>
                    <div class="my-2"><?= $languageService->translate('file_upload_max_size') ?> <?= $max_file_size ?></div>
                </div>

                <?php
                    if(count($errors) > 0) {
                        echo '<div class="flex flex-col gap-2">';
                        foreach($errors as $fileName => $reason) {
                            echo '<div class="flex flex-col justify-between p-2 rounded bg-red-300 text-red-800 border-2 border-red-800"><div class="col-span-1">' . $fileName . '</div><div class="col-span-1">' . $reason . '</div></div>';
                        }
                        echo '</div>';
                    }
?>

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
    echo '<script>setTimeout(function(){openToast("' . $languageService->translate('upload_success') . '")},500);</script>';
}
if (count($errors) > 0) {
    echo '<script>setTimeout(function(){openToast("' . $languageService->translate('upload_error') . '", "isError", 5000)},500);</script>';
}

include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
