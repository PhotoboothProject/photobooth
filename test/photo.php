<?php

require_once '../lib/boot.php';

use Photobooth\Image;
use Photobooth\Enum\FolderEnum;
use Photobooth\Enum\ImageFilterEnum;
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
    $name = date('Ymd_His') . '.jpg';
    $filename_tmp = FolderEnum::TEST->absolute() . DIRECTORY_SEPARATOR . $name;
    $imageHandler = new Image();
    $imageHandler->debugLevel = $config['dev']['loglevel'];
    $imageHandler->imageModified = false;

    $imageResource = $imageHandler->createFromImage(ImageUtility::getRandomImageFromPath('resources/img/demo'));
    if (!$imageResource) {
        throw new \Exception('Error creating image resource.');
    }
    $imageHandler->framePath = $config['picture']['frame'];

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
    $image_filter = $config['filters']['defaults'];
    if ($image_filter !== ImageFilterEnum::PLAIN) {
        try {
            ImageUtility::applyFilter($image_filter, $imageResource);
            $imageHandler->imageModified = true;
        } catch (\Exception $e) {
            throw new \Exception('Error applying image filter.');
        }
    }

    if ($config['picture']['polaroid_effect']) {
        $imageHandler->polaroidRotation = $config['picture']['polaroid_rotation'];
        $imageResource = $imageHandler->effectPolaroid($imageResource);
        if (!$imageResource) {
            throw new \Exception('Error applying polaroid effect.');
        }
    }

    if ($config['picture']['take_frame']) {
        $imageHandler->frameExtend = $config['picture']['extend_by_frame'];
        if ($config['picture']['extend_by_frame']) {
            $imageHandler->frameExtendLeft = $config['picture']['frame_left_percentage'];
            $imageHandler->frameExtendRight = $config['picture']['frame_right_percentage'];
            $imageHandler->frameExtendBottom = $config['picture']['frame_bottom_percentage'];
            $imageHandler->frameExtendTop = $config['picture']['frame_top_percentage'];
        }
        $imageResource = $imageHandler->applyFrame($imageResource);
        if (!$imageResource) {
            throw new \Exception('Error applying frame to image resource.');
        }
    }

    if ($config['picture']['rotation'] !== '0') {
        $imageHandler->resizeRotation = $config['picture']['rotation'];
        $imageResource = $imageHandler->rotateResizeImage($imageResource);
        if (!$imageResource) {
            throw new \Exception('Error resizing resource.');
        }
    }

    if ($config['textonpicture']['enabled']) {
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
            throw new \Exception('Error applying text to image resource.');
        }
    }

    if (!$imageHandler->saveJpeg($imageResource, $filename_tmp)) {
        throw new \Exception('Failed to save image.');
    }
    unset($imageResource);

} catch (\Exception $e) {
    $errorMessage = $e->getMessage();
    $logger->error($errorMessage);
}

$pageTitle = 'Picture test - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
?>

<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
            <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                Picture Test
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
?>
            </div>
        </div>
    </div>
</div>

<?php
include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
