<?php

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;

$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$languageService = LanguageService::getInstance();
$errorMessage = '';

try {
    $demoImages = ImageUtility::getDemoImages($config['collage']['limit']);

    $name = date('Ymd_His') . '.jpg';
    $collageSrcImagePaths = [];
    for ($i = 0; $i < $config['collage']['limit']; $i++) {
        $image = $demoImages[$i];
        $path = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $i . '_' . $name;
        if (!copy($image, $path)) {
            throw new \Exception('Failed to copy image.');
        }
        $collageSrcImagePaths[] = $path;
    }

    $filename_tmp = FolderEnum::TEST->absolute() . DIRECTORY_SEPARATOR . $name;
    if (Collage::createCollage($config, $collageSrcImagePaths, $filename_tmp, $config['filters']['defaults'])) {
        for ($k = 0; $k < $config['collage']['limit']; $k++) {
            unlink($collageSrcImagePaths[$k]);
        }
    }
} catch (\Exception $e) {
    $errorMessage = $e->getMessage();
    $logger->error($errorMessage);
}

$pageTitle = 'Collage test - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
?>

<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
            <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                Collage Test
            </div>
            <?php
                    if (empty($errorMessage)) {
                        echo '<div class="border border-solid border-black">';
                        echo '<img src="' . PathUtility::getPublicPath($filename_tmp) . '" alt="Test Image">';
                        echo '</div>';
                    } else {
                        echo '<div class="flex flex-col gap-2">';
                        echo '<div class="flex flex-col justify-between p-2 rounded bg-red-300 text-red-800 border-2 border-red-800"><div class="col-span-1">' . $errorMessage . '</div></div>';
                        echo '</div>';
                    }
?>
        </div>

        <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
        </div>

        <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
            <?php
echo getMenuBtn(PathUtility::getPublicPath('admin'), 'admin_panel', $config['icons']['admin']);
echo getMenuBtn(PathUtility::getPublicPath('test'), 'testMenu', $config['icons']['admin']);
echo getMenuBtn(PathUtility::getPublicPath('admin/generator'), 'layout_generator', $config['icons']['take_collage']);
?>
            </div>
        </div>
    </div>
</div>

<?php
include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
