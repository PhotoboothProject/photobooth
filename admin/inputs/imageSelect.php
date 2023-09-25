<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

function getImageSelect($setting, $label)
{
    $languageService = LanguageService::getInstance();

    $images = '';
    if (isset($setting['paths']) && is_array($setting['paths'])) {
        foreach ($setting['paths'] as $path) {
            $images .= '
                <div class="col-span-3">
                    <h2 class="font-bold">' . PathUtility::getPublicPath($path) . '</h2>
                </div>
            ';
            try {
                $files = ImageUtility::getImagesFromPath($path, false);
                $files = array_map(fn ($file): string => PathUtility::getPublicPath($file), $files);
                if (count($files) === 0) {
                    $images .= '
                        <div class="col-span-3">
                            <p>' . $languageService->translate('error.path.noimages') . '</p>
                        </div>
                    ';
                }
                foreach ($files as $file) {
                    $origin = $file;
                    if (str_contains($setting['value'], 'url(')) {
                        $origin = 'url(' . $origin . ')';
                    }
                    $images .= '
                        <div class="w-full relative h-0 pb-2/3">
                            <img
                                onclick="adminImageSelect(this, \'' . $setting['name'] . '\');"
                                data-origin="' . $origin . '"
                                class="w-full h-full left-0 top-0 absolute object-contain"
                                src="' . $file . '"
                                title="' . $file . '"
                            >
                        </div>
                    ';
                }
            } catch (\Exception $e) {
                $images .= '
                    <div class="col-span-3">
                        <p>' . $e->getMessage() . '</p>
                    </div>
                ';
            }
        }
    }

    $hiddenPreview = '';
    if (str_starts_with($setting['value'], 'http')) {
        $hiddenPreview = 'hidden';
    }

    $selectedImage = $setting['value'];
    if (str_contains($setting['value'], 'url(')) {
        $selectedImage = substr($setting['value'], 4, -1);
    }
    if (str_contains($setting['value'], $_SERVER['DOCUMENT_ROOT'])) {
        $selectedImage = substr($setting['value'], strlen($_SERVER['DOCUMENT_ROOT']));
    }
    $selectedImage = preg_replace('#/+#', '/', $selectedImage);

    return '
        <div class="adminImageSelection group">
            <div class="w-full flex items-start">
                <div class="w-24 flex mb-3 mr-3 shrink-0 cursor-pointer ' . $hiddenPreview . '" onclick="openAdminImageSelect(this)">
                    <img class="adminImageSelection-preview object-contain" src="' . $selectedImage . '">
                </div>
                <div class="w-full flex flex-col">
                    ' . getHeadline($label) . '
                    <div class="text-xs mb-3 -mt-2 break-all">
                        ' . $setting['value'] . '
                    </div>
                    ' . ($images !== '' ? '<div class="w-full mb-3 h-10 bg-brand-1 text-white flex items-center justify-center rounded-full" onclick="openAdminImageSelect(this)">' . $languageService->translate('choose_image') . '</div>' : '') . '
                </div>
            </div>
            <div class="hidden group-[&.isOpen]:grid w-full h-full fixed left-0 top-0 z-50 place-items-center">
                <div class="w-full h-full left-0 top-0 z-10 absolute bg-black bg-opacity-60 cursor-pointer" onclick="closeAdminImageSelect()"></div>
                <div class="w-full max-h-3/4 max-w-2xl bg-white p-4 pt-2 rounded relative z-20 flex flex-col overflow-hidden">
                    <div class="w-full flex items-center">
                        <h2 class="flex text-brand-1 font-bold">
                            ' . $languageService->translate('choose_image') . '
                        </h2>
                        <div class="ml-auto flex items-center justify-center p-3 text-xl fa fa-close" onclick="closeAdminImageSelect()"></div>
                    </div>
                    <div class="flex w-full h-full flex-col overflow-y-auto">
                        <div class="grid grid-cols-3 gap-4">
                            ' . $images . '
                        </div>
                    </div>
                </div>
            </div>
            <input
                type="input"
                class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
            />
        </div>
    ';
}
