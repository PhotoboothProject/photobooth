<?php

require_once '../../lib/boot.php';

use Photobooth\FileUploader;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
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

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$languageService = LanguageService::getInstance();
$pageTitle = 'File uploader - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$errors = [];
$failedFiles = [];
$success = false;
$folderName = null;
$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mb-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
$max_file_size = ini_get('upload_max_filesize');

if (isset($_POST['submit'])) {
    $folderName = $_POST['folder_name'];
    $uploadedFiles = $_FILES['files'];

    $uploader = new FileUploader($folderName, $uploadedFiles, $logger);
    $response = $uploader->uploadFiles();

    list($success, $message, $errors, $uploadedFiles, $failedFiles) = [
        $response['success'],
        $response['message'],
        $response['errors'],
        $response['uploadedFiles'],
        $response['failedFiles']
    ];
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
                        <option value="private/images/background" <?= $folderName === 'private/images/background' ? 'selected' : '' ?>>images/background</option>
                        <option value="private/images/keyingBackgrounds" <?= $folderName === 'private/images/keyingBackgrounds' ? 'selected' : '' ?>>images/keyingBackgrounds</option>
                        <option value="private/images/frames" <?= $folderName === 'private/images/frames' ? 'selected' : '' ?>>images/frames</option>
                        <option value="private/images/logo" <?= $folderName === 'private/images/logo' ? 'selected' : '' ?>>images/logo</option>
                        <option value="private/images/placeholder" <?= $folderName === 'private/images/placeholder' ? 'selected' : '' ?>>images/placeholder</option>
                        <option value="private/images/cheese" <?= $folderName === 'private/images/cheese' ? 'selected' : '' ?>>images/cheese</option>
                        <option value="private/images/demo" <?= $folderName === 'private/images/demo' ? 'selected' : '' ?>>images/demo</option>
                        <option value="private/videos/background" <?= $folderName === 'private/videos/background' ? 'selected' : '' ?>>videos/background</option>
                        <option value="private/fonts" <?= $folderName === 'private/fonts' ? 'selected' : '' ?>>fonts</option>
                    </select>
                    <label class="<?= $labelClass ?>" for="files"><?=$languageService->translate('upload_selection')?></label>
                    <input class="<?= $labelClass ?>" type="file" name="files[]" id="files" multiple accept="image/*, video/*, .ttf" required>
                    <div class="my-2"><?= $languageService->translate('file_upload_max_size') ?> <?= $max_file_size ?></div>
                </div>

                <?php
                    if (count($failedFiles) > 0) {
                        echo '<div class="flex flex-col gap-2">';
                        foreach ($failedFiles as $fileName => $reason) {
                            echo '<div class="flex flex-col justify-between p-2 rounded bg-red-300 text-red-800 border-2 border-red-800"><div class="col-span-1">' . $fileName . '</div><div class="col-span-1">' . $languageService->translate($reason) . '</div></div>';
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
