<?php

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Image;
use Photobooth\Enum\FolderEnum;
use Photobooth\Processor\ImageProcessor;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$languageService = LanguageService::getInstance();
$errorMessage = '';
$database = null;
$processor = null;

try {
    $vars['fileName'] = date('Ymd_His') . '.jpg';
    $vars['style'] = 'collage';
    $vars['imageFilter'] = $config['filters']['defaults'];
    $vars['isCollage'] = true;
    $vars['editSingleCollage'] = false;
    $vars['isChroma'] = false;
    $vars['srcImages'] = [];
    $vars['srcImages'][] = $vars['fileName'];
    $vars['singleImageFile'] = $vars['fileName'];
    $vars['tmpFile'] = FolderEnum::TEST->absolute() . DIRECTORY_SEPARATOR . $vars['fileName'];
    $vars['resultFile'] = $vars['tmpFile'];

    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $imageHandler->imageModified = false;

    if (class_exists('Photobooth\Processor\ImageProcessor')) {
        $processor = new ImageProcessor($imageHandler, $logger, $database, $vars, $config);
    }

    $demoImages = ImageUtility::getDemoImages($config['collage']['limit']);
    for ($i = 0; $i < $config['collage']['limit']; $i++) {
        $image = $demoImages[$i];
        $path = FolderEnum::TEMP->absolute() . DIRECTORY_SEPARATOR . $i . '_' . $vars['fileName'];
        if (!copy($image, $path)) {
            throw new \Exception('Failed to copy image.');
        }
        $vars['collageSrcImagePaths'][] = $path;
    }

    if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'preCollageProcessing')) {
        list($imageHandler, $vars, $config) = $processor->preCollageProcessing($imageHandler, $vars, $config);
    }

    if (Collage::createCollage($config, $vars['collageSrcImagePaths'], $vars['tmpFile'], $vars['imageFilter'])) {
        for ($k = 0; $k < $config['collage']['limit']; $k++) {
            unlink($vars['collageSrcImagePaths'][$k]);
        }
    }

    if ($processor !== null && $processor instanceof ImageProcessor && method_exists($processor, 'postCollageProcessing')) {
        list($imageHandler, $vars, $config) = $processor->postCollageProcessing($imageHandler, $vars, $config);
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
                <?=$languageService->translate('collageTest')?>
            </div>
            <?php
                    if (empty($errorMessage)) {
                        echo '<div class="border border-solid border-black">';
                        echo '<img src="' . PathUtility::getPublicPath($vars['tmpFile']) . '" alt="Test Image">';
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
