<?php

namespace Photobooth\Utility;

use Photobooth\Enum\Interface\LabelInterface;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\ThemeService;

class AdminInput
{
    protected static bool $themeField = false;

    public static function setThemeFieldFlag(bool $isThemeField): void
    {
        self::$themeField = $isThemeField;
    }
    protected static function buildAttributes(array $setting): string
    {
        $attributes = '';

        if (isset($setting['attributes'])) {
            foreach ($setting['attributes'] as $key => $prop) {
                $attributes .= $key . '="' . $prop . '" ';
            }
        }

        foreach ($setting as $key => $value) {
            if (str_starts_with($key, 'data-')) {
                $attributes .= $key . '="' . $value . '" ';
            }
        }

        return $attributes;
    }

    public static function renderInput(array $setting, string $label): string
    {
        $attributes = self::buildAttributes($setting);

        return self::renderHeadline($label) . '
            <input
                class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                type="' . $setting['type'] . '"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
				' . $attributes . '
            />
        ';
    }

    public static function renderHidden(array $setting): string
    {
        return '<input type="hidden" name="' . $setting['name'] . '" value="' . $setting['value'] . '"/>';
    }

    public static function renderInfo(array $setting, string $label): string
    {
        $value = htmlspecialchars((string) ($setting['value'] ?? ''), ENT_QUOTES);

        return self::renderHeadline($label) . '
            <div class="mt-auto font-mono text-sm text-brand-1 break-all">' . $value . '</div>
        ';
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
            <button class="w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-content-1 hover:text-brand-1 transition font-bold [&.isDirty]:bg-amber-500 [&.isDirty]:border-amber-500 [&.isDirty]:text-black" id="' . $btnId . '">
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
        $info = '';
        switch ($key) {
            case 'check_version':
                $languageService = LanguageService::getInstance();
                $currentVersion = ApplicationService::getInstance()->getVersion();
                $info = '
                    <table id="version_text_table" class="mb-2">
                        <tr>
                            <td><span id="current_version_text">' . $languageService->translate('current_version') . '</span></td>
                            <td><span id="current_version">' . $currentVersion . '</span></td>
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

        return self::renderHeadline($label) .
            $info . '
            <div class="w-full flex flex-col">
                ' . $btn . '
            </div>
        ';
    }

    public static function renderCheckbox(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $checkboxClasses =
            "w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600";
        $init = $setting['value'];
        $note = $setting['note'] ?? '';

        $attributes = self::buildAttributes($setting);

        return self::renderHeadline($label)
            . ($note !== '' ? '<div class="mt-2 text-xs text-gray-600 mb-2">' . htmlspecialchars((string) $note, ENT_QUOTES) . '</div>' : '') . '
            <label class="adminCheckbox relative inline-flex items-center cursor-pointer mt-auto">
                <input type="hidden" name="' . $setting['name'] . '" value="false" />
                <input class="hidden peer" type="checkbox" ' . ($setting['value'] == 'true' ? ' checked="checked"' : '') . ' name="' . $setting['name'] . '" value="true" ' . $attributes . ' />
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
        $attributes = self::buildAttributes($setting);

        return '
            <label class="mb-3">' . self::renderHeadline($label) . '</label>
            <input
                class="w-full h-10 border-2 border-gray-300 border-solid rounded-lg overflow-hidden p-1 mt-auto"
                type="color"
                name="' . $setting['name'] . '"
                value="' . $setting['value'] . '"
                placeholder="' . $setting['placeholder'] . '"
				' . $attributes . '
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
        $attributes = self::buildAttributes($setting);

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
					' . $attributes . '
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

        $attributes = self::buildAttributes($setting);

        foreach ($setting['options'] as $value => $option) {
            $optionLabel = $option;
            $optionValue = $value;
            if ($option instanceof \BackedEnum) {
                $optionLabel = ($option instanceof LabelInterface) ? $option->label() : $option->name;
                $optionValue = $option;
            }

            $selected = '';
            if ((is_array($setting['value']) && in_array($optionValue, $setting['value'])) || $optionValue === $setting['value']) {
                $selected = ' selected="selected"';
            }
            $styles = '';
            if ($settingName === 'text_font_family') {
                $styles = 'style="font-family:' . $optionLabel . '"';
            }
            $options .= '<option ' . $selected . ' value="' . ($optionValue instanceof \BackedEnum ? $optionValue->value : $optionValue) . '"' . $styles . '>' . $optionLabel . '</option>';
        }

        return self::renderHeadline($label) . '
            <select
                class="' . $className . '"
                name="' . $settingName . '"
                ' . ($setting['type'] === 'multi-select' ? ' multiple="multiple"' : '') . '
				        ' . $attributes . '
            >
                ' . $options . '
            </select>
        ';
    }

    public static function renderImageSelect(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $images = '';

        $attributes = self::buildAttributes($setting);

        if (isset($setting['paths']) && is_array($setting['paths'])) {
            foreach ($setting['paths'] as $path) {
                $relativeBase = str_replace(PathUtility::getRootPath(), '', $path);

                // Heading for each path in file selector
                $images .= '
                <div class="col-span-3">
                    <h2 class="font-bold">' . $relativeBase . '</h2>
                </div>
            ';
                try {
                    $files = ImageUtility::getImagesFromPath($path, false);
                    if (count($files) === 0) {
                        $images .= '
                        <div class="col-span-3">
                            <p>' . $languageService->translate('error_path_noImages') . '</p>
                        </div>
                    ';
                    }
                    foreach ($files as $file) {
                        $publicPath = PathUtility::getPublicPath($file);
                        // Store project relative path
                        $relativeImagePathToStore = str_replace(PathUtility::getRootPath(), '', $file);
                        $filename                 = basename($file);

                        $images .= '
                        <div class="w-full">
                            <div class="relative h-0 pb-2/3 cursor-pointer hover:shadow-lg" >
                                <img
                                    onclick="adminImageSelect(this, \'' . $setting['name'] . '\');"
                                    data-origin="' . $relativeImagePathToStore . '"
                                    class="w-full h-full left-0 top-0 absolute object-contain"
                                    src="' . $publicPath . '"
                                    title="' . $publicPath . '"
                                >
                            </div>
                            <div class="w-full text-center text-xs text-gray-700 truncate">
                                ' . $filename . '
                            </div>
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
        if (empty($setting['value'])) {
            $hiddenPreview = 'hidden';
        }
        if (str_starts_with($setting['value'], 'http')) {
            $hiddenPreview = 'hidden';
        }

        $selectedImage = $setting['value'];
        $selectedImagePublic = $selectedImage !== '' ? PathUtility::getPublicPath($selectedImage) : '';

        return '
            <div class="adminImageSelection group">
                <div class="w-full flex items-start">
                    <div class="w-24 flex mb-3 mr-3 shrink-0 cursor-pointer ' . $hiddenPreview . '" onclick="openAdminImageSelect(this)">
                        <img class="adminImageSelection-preview object-contain border border-brand-1 hover:shadow-lg" src="' . $selectedImagePublic . '">
                    </div>
                    <div class="w-full flex flex-col">
                        ' . self::renderHeadline($label) . '
                        <div class="adminImageSelection-text text-xs mb-3 -mt-2 break-all">
                            ' . $setting['value'] . '
                        </div>
                        ' . ($images !== '' ? '<div class="w-full mb-3 h-10 bg-brand-1 text-white flex items-center justify-center rounded-full" onclick="openAdminImageSelect(this)">' . $languageService->translate('choose_image') . '</div>' : '') . '
                    </div>
                </div>
                <div class="hidden group-[&.isOpen]:grid w-full h-full fixed left-0 top-0 z-50 place-items-center">
                    <div class="w-full h-full left-0 top-0 z-10 absolute bg-black/60 cursor-pointer" onclick="closeAdminImageSelect()"></div>
                    <div class="w-full max-h-3/4 max-w-2xl bg-white p-4 pt-2 rounded-sm relative z-20 flex flex-col overflow-hidden">
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
					' . $attributes . '
                />
            </div>
        ';
    }

    public static function renderFontSelect(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $fonts = '';

        $attributes = self::buildAttributes($setting);

        if (isset($setting['paths']) && is_array($setting['paths'])) {
            $pathIndex = 0;
            foreach ($setting['paths'] as $path) {
                $relativeBase = str_replace(PathUtility::getRootPath(), '', $path);

                $fonts .= '
                <div class="col-span-3">
                    <h2 class="font-bold">' . $relativeBase . '</h2>
                </div>
            ';
                $fontClassName = 'font-' . $pathIndex;
                try {
                    $files = FontUtility::getFontsFromPath($path, false);
                    if (count($files) === 0) {
                        $fonts .= '
                        <div class="col-span-3">
                            <p>' . $languageService->translate('error_path_noFonts') . '</p>
                        </div>
                    ';
                    }
                    $fontIndex = 0;
                    foreach ($files as $name => $fontPath) {
                        $fontClassName .= '-' . $fontIndex;
                        // Public URL for preview image
                        $publicPath = PathUtility::getPublicPath($fontPath);
                        // Project-relative path (no installation root, no leading slash) to be stored in config
                        $origin = str_replace(PathUtility::getRootPath(), '', $fontPath);
                        $origin = ltrim($origin, '/');
                        $imageAttributes = [
                            'onClick' => 'adminFontSelect(this, "' . $setting['name'] . '", "' . $fontClassName . '");',
                            'data-origin' => $origin,
                            'title' => $name,
                            'class' => 'w-full h-full left-0 top-0 absolute object-contain cursor-pointer hover:shadow-lg',
                        ];
                        $fonts .= '<style>.' . $fontClassName . ' {font-family:"' . $name . '"}</style>';
                        $fonts           .= '<div class="w-full relative h-0 pb-2/3">' . FontUtility::getFontPreviewImage(fontPath: $publicPath, attributes: $imageAttributes) . '</div>';
                        $fontIndex++;
                    }
                } catch (\Exception $e) {
                    $fonts .= '
                    <div class="col-span-3">
                        <p>' . $e->getMessage() . '</p>
                    </div>
                ';
                }
                $pathIndex++;
            }
        }

        $selectedFont = $setting['value'];

        return '
            <div class="adminFontSelection group">
                <div class="w-full flex items-start">
                    <div class="w-24 flex mb-3 mr-3 shrink-0 cursor-pointer border border-brand-1  hover:shadow-lg" onclick="openAdminFontSelect(this)">
                        ' . FontUtility::getFontPreviewImage(fontPath: $selectedFont, attributes: ['class' => 'adminFontSelection-preview object-contain']) . '
                    </div>
                    <div class="w-full flex flex-col">
                        ' . self::renderHeadline($label) . '
                        <div class="adminFontSelection-text text-xs mb-3 -mt-2 break-all">
                            ' . $setting['value'] . '
                        </div>
                        ' . ($fonts !== '' ? '<div class="w-full mb-3 h-10 bg-brand-1 text-white flex items-center justify-center rounded-full" onclick="openAdminFontSelect(this)">' . $languageService->translate('choose_font') . '</div>' : '') . '
                    </div>
                </div>
                <div class="hidden group-[&.isOpen]:grid w-full h-full fixed left-0 top-0 z-50 place-items-center">
                    <div class="w-full h-full left-0 top-0 z-10 absolute bg-black/60 cursor-pointer" onclick="closeAdminFontSelect()"></div>
                    <div class="w-full max-h-3/4 max-w-2xl bg-white p-4 pt-2 rounded-sm relative z-20 flex flex-col overflow-hidden">
                        <div class="w-full flex items-center">
                            <h2 class="flex text-brand-1 font-bold">
                                ' . $languageService->translate('choose_font') . '
                            </h2>
                            <div class="ml-auto flex items-center justify-center p-3 text-xl fa fa-close" onclick="closeAdminFontSelect()"></div>
                        </div>
                        <div class="flex w-full h-full flex-col overflow-y-auto">
                            <div class="grid grid-cols-3 gap-4">
                                ' . $fonts . '
                            </div>
                        </div>
                    </div>
                </div>
                <input
                    type="input"
                    class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
                    name="' . $setting['name'] . '"
                    value="' . $setting['value'] . '"
					' . $attributes . '
                />
            </div>
        ';
    }

    public static function renderTheme(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $currentTheme = $setting['current'] ?? '';

        $themes = ThemeService::getInstance()->getAll();
        $themeNames = array_keys($themes);
        sort($themeNames);

        $options = '
            <option value="">
                ' . $languageService->translate('theme_choose') . '
            </option>
        ';
        foreach ($themeNames as $name) {
            $options .= '<option value="' . htmlspecialchars($name, ENT_QUOTES) . '">' . htmlspecialchars($name, ENT_QUOTES) . '</option>';
        }

        return '
            ' . self::renderHeadline($label) . '
            <div class="flex flex-col gap-2">
                <div class="flex flex-col md:flex-row gap-2 items-stretch md:items-center">
                    <input
                        type="hidden"
                        name="theme[current]"
                        value="' . htmlspecialchars($currentTheme, ENT_QUOTES) . '"
                    />
                    <input
                        id="theme-name"
                        type="text"
                        class="flex-1 h-9 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 text-sm"
                        placeholder="' . $languageService->translate('theme_name_placeholder') . '"
                    />
                    <select
                        id="theme-select"
                        class="flex-1 h-9 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 text-sm"
                    >
                        ' . $options . '
                    </select>
                </div>
                <div class="flex flex-row gap-2 items-center justify-between">
                    <div class="flex flex-row gap-2">
                        <button
                            id="theme-save-btn"
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-full bg-brand-1 text-white border border-solid border-brand-1 hover:bg-content-1 hover:text-brand-1 transition text-[10px] font-bold"
                            title="' . $languageService->translate('theme_save') . '"
                        >
                            <i class="fa fa-save"></i>
                        </button>
                    </div>
                    <div class="flex flex-row gap-2">
                        <button
                            id="theme-load-btn"
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-full bg-content-1 text-brand-1 border border-solid border-brand-1 hover:bg-brand-1 hover:text-white transition text-[10px] font-bold"
                            title="' . $languageService->translate('theme_load') . '"
                        >
                            <i class="fa fa-download"></i>
                        </button>
                        <button
                            id="theme-delete-btn"
                            type="button"
                            class="h-8 w-8 flex items-center justify-center rounded-full bg-content-1 text-red-600 border border-solid border-red-600 hover:bg-red-600 hover:text-white transition text-[10px] font-bold"
                            title="' . $languageService->translate('theme_delete') . '"
                        >
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        ';
    }

    public static function renderVideoSelect(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $videos = '';

        if (isset($setting['paths']) && is_array($setting['paths'])) {
            foreach ($setting['paths'] as $path) {
                $relativeBase = str_replace(PathUtility::getRootPath(), '', $path);

                $videos .= '
                <div class="col-span-3">
                    <h2 class="font-bold">' . $relativeBase . '</h2>
                </div>
            ';
                try {
                    $files = VideoUtility::getVideosFromPath($path, false);
                    if (count($files) === 0) {
                        $videos .= '
                        <div class="col-span-3">
                            <p>' . $languageService->translate('error_path_noVideos') . '</p>
                        </div>
                    ';
                    }
                    foreach ($files as $file) {
                        // Store project relative path
                        $relativeVideoPath = str_replace(PathUtility::getRootPath(), '', $file);
                        $filename          = basename($file);
                        $videoAttributes = [
                            'onClick' => 'adminVideoSelect(this, "' . $setting['name'] . '");',
                            'data-origin' => $relativeVideoPath,
                            'title' => $file,
                            'class'       => 'w-full h-full left-0 top-0 absolute object-contain cursor-pointer hover:shadow-lg',
                        ];
                        $videos            .= '
                            <div class="w-full">
                                <div class="relative h-0 pb-2/3 cursor-pointer hover:shadow-lg">' .
                                              VideoUtility::getVideoPreview($relativeVideoPath, $videoAttributes) . '
                                </div>
                                <div class="w-full text-center text-xs text-gray-700 truncate">
                                    ' . $filename . '
                                </div>
                            </div>
                        ';
                    }
                } catch (\Exception $e) {
                    $videos .= '
                    <div class="col-span-3">
                        <p>' . $e->getMessage() . '</p>
                    </div>
                ';
                }
            }
        }

        $selectedVideo = $setting['value'];

        return '
            <div class="adminVideoSelection group">
                <div class="w-full flex items-start">
                    <div class="w-24 flex mb-3 mr-3 shrink-0 cursor-pointer border border-brand-1  hover:shadow-lg" onclick="openAdminVideoSelect(this)">
                        ' . VideoUtility::getVideoPreview($selectedVideo, ['class' => 'adminVideoSelection-preview object-contain']) . '
                    </div>
                    <div class="w-full flex flex-col">
                        ' . self::renderHeadline($label) . '
                        <div class="adminVideoSelection-text text-xs mb-3 -mt-2 break-all">
                            ' . $setting['value'] . '
                        </div>
                        ' . ($videos !== '' ? '<div class="w-full mb-3 h-10 bg-brand-1 text-white flex items-center justify-center rounded-full" onclick="openAdminVideoSelect(this)">' . $languageService->translate('choose_video') . '</div>' : '') . '
                    </div>
                </div>
                <div class="hidden group-[&.isOpen]:grid w-full h-full fixed left-0 top-0 z-50 place-items-center">
                    <div class="w-full h-full left-0 top-0 z-10 absolute bg-black/60 cursor-pointer" onclick="closeAdminVideoSelect()"></div>
                    <div class="w-full max-h-3/4 max-w-2xl bg-white p-4 pt-2 rounded-sm relative z-20 flex flex-col overflow-hidden">
                        <div class="w-full flex items-center">
                            <h2 class="flex text-brand-1 font-bold">
                                ' . $languageService->translate('choose_video') . '
                            </h2>
                            <div class="ml-auto flex items-center justify-center p-3 text-xl fa fa-close" onclick="closeAdminVideoSelect()"></div>
                        </div>
                        <div class="flex w-full h-full flex-col overflow-y-auto">
                            <div class="grid grid-cols-3 gap-4">
                                ' . $videos . '
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
            absolute z-20 hidden flex-col px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-lg
            w-80 max-w-[calc(100vw-4rem)] left-0 top-full mt-2 break-words
            peer-hover:flex peer-focus:flex
        ';

        $isThemeField = self::$themeField;

        return '
            <div class="tooltip mb-3 relative flex items-center justify-between gap-2">
                <label class="peer text-black text-md font-bold inline-flex items-center gap-2 cursor-help">
                    <span>' . $languageService->translate($label) . '</span>
                </label>
                ' . ($isThemeField ? '<span class="text-[10px] font-semibold uppercase tracking-wide text-brand-1">Theme</span>' : '') . '
                <span class="' . $tooltipClass . '">
                    <div class="absolute left-4 -top-[10px] h-0 w-0 border-x-8 border-x-transparent border-b-[10px] border-gray-900"></div>
                    ' . $languageService->translate('manual:' . $label) . '
                </span>
            </div>
        ';
    }

    public static function renderList(array $setting, string $label): string
    {
        $languageService = LanguageService::getInstance();
        $items = is_array($setting['value']) ? $setting['value'] : [];
        $name = $setting['name'] . '[]';
        $placeholder = $setting['placeholder'] ?? '';

        $html = self::renderHeadline($label) . '
        <div class="adminList" data-name="' . $setting['name'] . '">
            <div class="adminList-items flex flex-col gap-2">';

        foreach ($items as $value) {
            $value = htmlspecialchars($value, ENT_QUOTES);
            $html .= '
            <div class="adminList-item flex gap-2">
                <input type="text"
                       name="' . $name . '"
                       value="' . $value . '"
                       class="w-full h-10 border-2 border-gray-300 rounded-md px-3"/>
                <button type="button"
                        class="adminList-remove bg-red-500 text-white px-3 rounded-md"
                        onclick="this.parentElement.remove()">
                    ×
                </button>
            </div>';
        }

        $html .= '
            </div>

            <button type="button"
                    class="adminList-add mt-3 bg-brand-1 text-white px-4 h-10 rounded-md"
                    onclick="adminListAdd(this)">
                + ' . $languageService->translate('add_entry') . '
            </button>
        </div>

        <script>
        function adminListAdd(button) {
            const container = button.parentElement.querySelector(".adminList-items");
            const name = button.parentElement.dataset.name + "[]";
            const item = document.createElement("div");
            item.className = "adminList-item flex gap-2";
            item.innerHTML = `
                <input type="text"
                       name="` + name + `"
                       value=""
                       class="w-full h-10 border-2 border-gray-300 rounded-md px-3"/>
                <button type="button"
                        class="adminList-remove bg-red-500 text-white px-3 rounded-md"
                        onclick="this.parentElement.remove()">
                    ×
                </button>
            `;
            container.appendChild(item);
        }
        </script>
    ';

        return $html;
    }
}
