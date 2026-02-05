<?php
// admin/collage-designer/components/collage-designer-txtSetPnl.php
// -> txt-settings-panel

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

?>

<div id="text_specific_settings_panel" class="flex flex-col gap-4 p-4 rounded-md bg-white w-full">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1">
        <span class="flex items-baseline gap-1">
            <?= $languageService->translate('txt_settings_title') ?>
        </span>
        <span id="selected_text_element_id_display" class="text-sm font-normal text-gray-500 hidden"></span>
    </span>
    <div class="flex flex-wrap items-center gap-2 h-min justify-content-between">
        <!-- Text Buttons -->
        <button id="txtIncr" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('increse_text_size') ?>">
            <i class="material-icons">text_increase</i>
        </button>
        <button id="txtDecr" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('decrease_text_size') ?>">
            <i class="material-icons">text_decrease</i>
        </button>
        <button id="txtBold" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('bold') ?>">
            <i class="material-icons">format_bold</i>
        </button>
        <button id="txtIalic" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('italic') ?>">
            <i class="material-icons">format_italic</i>
        </button>
        <button id="txtUnderline" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('underlined') ?>">
            <i class="material-icons">format_underlined</i>
        </button>
        <button id="txtAlignLeft" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_left') ?>">
            <i class="material-icons">format_align_left</i>
        </button>
        <button id="txtAlignHorCenter" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_horizontal_center') ?>">
            <i class="material-icons">format_align_center</i>
        </button>
        <button id="txtAlignRight" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_right') ?>">
            <i class="material-icons">format_align_right</i>
        </button>
        <button id="txtAlignVerTop" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_top') ?>">
            <i class="material-icons">vertical_align_top</i>
        </button>
        <button id="txtAlignVerCenter" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_vertical_center') ?>">
            <i class="material-icons">vertical_align_center</i>
        </button>
        <button id="txtAlignVerBottom" class="btn btn-sm btn-outline-primary rounded-md flex items-center justify-center" title="<?= $languageService->translate('align_bottom') ?>">
            <i class="material-icons">vertical_align_bottom</i>
        </button>
    </div>
    <input
        type="input"
        class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto"
        name="active_txt_element_content"
        value=""
    />
    <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderFontSelect(
                    [
                        'name' => 'textoncollage[font]',
                        'value' => '',
                        'paths' => [
                            PathUtility::getAbsolutePath('resources/fonts'),
                            PathUtility::getAbsolutePath('private/fonts'),
                        ]
                    ],
                    'collage:textoncollage_font'
                )
?>
    </div>
    <div class="col-span-2 flex flex-col">
            <?=
    AdminInput::renderColor(
        [
            'name' => 'textoncollage[font_color]',
            'value' => '',
            'placeholder' => $defaultConfig['textoncollage']['font_color'],
        ],
        'collage:textoncollage_font_color'
    )
?>
    </div>
    

</div>
