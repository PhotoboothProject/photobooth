<?php

namespace Photobooth\Utility;

use Photobooth\Enum\Interface\LabelInterface;
use Photobooth\Service\LanguageService;

class AdminInput
{
    public static function renderInput(array $setting, string $label): string
    {
        return self::renderHeadline($label) . '
            <input
                class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                type="' . ($setting['type'] === 'number' ? 'number' : 'text') . '"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
            />
        ';
    }

    public static function renderHidden(array $setting): string
    {
        return '<input type="hidden" name="' . $setting['name'] . '" value="' . $setting['value'] . '"/>';
    }

    public static function renderCta(string $label, string $btnId = '', ?array $config = null): string
    {
        $languageService = LanguageService::getInstance();

        $labels = '';
        if ($config !== null) {
            $labels = '
                <span class="hidden success"><i class="' . $config['icons']['admin_save_success'] . '"></i> ' . $languageService->translate('success') . '</span>
                <span class="hidden error"><i class="' . $config['icons']['admin_save_error'] . '"></i> ' . $languageService->translate('saveerror') . '</span>
            ';
        }

        return '
            <button class="w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-content-1 hover:text-brand-1 transition font-bold" id="' . $btnId . '">
                <span class="save">
                    ' . $languageService->translate($label) . '
                </span>
                ' . $labels . '
            </button>
        ';
    }

    public static function renderButton(array $setting, string $label, string $key, ?array $config = null): string
    {
        $btn = self::renderCta($setting['placeholder'], $setting['value'], $config);
        $test = '';
        switch ($key) {
            case 'check_version':
                $test = '
                    <table id="version_text_table">
                        <tr>
                            <td><span id="current_version_text"></span></td>
                            <td><span id="current_version"></span></td>
                        </tr>
                        <tr>
                            <td><span id="available_version_text"></span></td>
                            <td><span id="available_version"></span></td>
                        </tr>
                    </table>
                ';
                break;
            default:
                break;
        }

        return self::renderHeadline($label) . '
            <div class="w-full flex flex-col mt-auto">
                ' . $btn . '
            </div>
            ' . $test . '
        ';
    }

    public static function renderCheckbox(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $checkboxClasses =
            "w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600";
        $init = $setting['value'];

        return self::renderHeadline($label) . '
            <label class="adminCheckbox relative inline-flex items-center cursor-pointer mt-auto">
                <input class="hidden peer" type="checkbox" ' . ($setting['value'] == 'true' ? ' checked="checked"' : '') . ' name="' . $setting['name'] . '" value="true" />
                <div class="' . $checkboxClasses . '"></div>
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                    <label class="adminCheckbox-true ' . ($init == 'true' ? '' : 'hidden') . '">' . $languageService->translate('adminpanel_toggletextON') . '</label>
                    <label class="adminCheckbox-false ' . ($init != 'true' ? '' : 'hidden') . '">' . $languageService->translate('adminpanel_toggletextOFF') . '</label>
                </span>
            </label>
        ';
    }

    public static function renderColor(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();

        return '
            <label class="mb-3">' . $languageService->translate($label) . '</label>
            <input
                class="w-full h-10 border-2 border-gray-300 border-solid rounded-lg overflow-hidden p-1 mt-auto"
                type="color"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
            />
        ';
    }

    public static function renderIcon(array $setting, string $label): string
    {
        return self::renderHeadline($label) . '
            ' . ($setting['value'] !== '' ? '<div class="text-center mb-3 p-3 border-2 border-solid border-gray-300 rounded-md"><i class="' . $setting['value'] . '"></i></div>' : '') . '
            <input
                class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                type="' . ($setting['type'] === 'number' ? 'number' : 'text') . '"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
            />
        ';
    }

    public static function renderRange(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $inputClass = 'adminRangeInput w-full h-2 mb-1 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700';

        return self::renderHeadline($label) . '
            <div class="w-full flex flex-col mt-auto">
                <label id="' . $setting['name'] . '-value" for="' . $setting['name'] . '" class="block mb-3 text-sm font-bold text-gray-900 dark:text-white">
                    <span class="mr-1">' . $setting['value'] . '</span>
                    ' . ($setting['unit'] == 'empty' ? '' : $languageService->translate($setting['unit'])) . '
                </label>
                <input
                    type="range"
                    name="' . $setting['name'] . '"
                    class="' . $inputClass . '"
                    value="' . $setting['value'] . '"
                    min="' . $setting['range_min'] . '"
                    max="' . $setting['range_max'] . '"
                    step="' . $setting['range_step'] . '"
                    placeholder="' . $setting['placeholder'] . '"
                />
                <div class="w-full flex text-gray-300">
                    <span>' . $setting['range_min'] . '</span>
                    <span class="ml-auto">' . $setting['range_max'] . '</span>
                </div>
            </div>
        ';
    }

    public static function renderSelect(array $setting, string $label): string
    {
        $className = $setting['type'] === 'multi-select' ? 'min-h-[30px] h-32 resize-y ' : '';
        $className .= 'w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 mt-auto';
        $settingName = $setting['name'] . '' . ($setting['type'] === 'multi-select' ? '[]' : '');
        $options = '';

        foreach ($setting['options'] as $value => $option) {
            $optionLabel = $option;
            $optionValue = $value;
            if ($option instanceof \UnitEnum) {
                $optionLabel = ($option instanceof LabelInterface) ? $option->label() : $option->name;
                $optionValue = $option->value;
            }

            $selected = '';
            if ((is_array($setting['value']) && in_array($optionValue, $setting['value'])) || $optionValue === $setting['value']) {
                $selected = ' selected="selected"';
            }
            $options .= '<option ' . $selected . ' value="' . $optionValue . '">' . $optionLabel . '</option>';
        }

        return self::renderHeadline($label) . '
            <select
                class="' . $className . '"
                name="' . $settingName . '"
                ' . ($setting['type'] === 'multi-select' ? ' multiple="multiple"' : '') . '
            >
                ' . $options . '
            </select>
        ';
    }

    public static function renderImageSelect(array $setting, string $label): string
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
                        ' . self::renderHeadline($label) . '
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

    protected static function renderHeadline(string $label): string
    {
        $languageService = LanguageService::getInstance();

        $tooltipClass = '
            absolute z-10 hidden flex-col px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm
            mt-3
            peer-hover:flex
        ';

        return '
            <div class="tooltip mb-3 relative">
                <label class="peer text-black text-md font-bold">' . $languageService->translate($label) . '</label>
                <span class="' . $tooltipClass . '">
                    <div class="absolute left-5 -top-[10px] h-0 w-0 border-x-8 border-x-transparent border-b-[10px] border-gray-900"></div>
                    ' . $languageService->translate('manual:' . $label) . '
                </span>
            </div>
        ';
    }
}
