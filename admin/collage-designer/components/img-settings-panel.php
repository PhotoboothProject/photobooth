<?php
// admin/collage-designer/components/img-settings-panel.php

use Photobooth\Utility\AdminInput;

?>

<div id="image_specific_settings_panel" class="flex flex-col gap-4 p-4 rounded-md bg-white w-full hidden">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1">
        <span class="flex items-baseline gap-1">
            <?= $languageService->translate('img_settings_title') ?>
        </span>
        <span id="selected_image_element_id_display" class="text-sm font-normal text-gray-500 hidden"></span>
    </span>

    <!-- Aspect Ratio Section for Images -->
    <div id="image_aspect_ratio_settings" class="mt-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
            <i class="material-icons text-brand-1">aspect_ratio</i> <?= $languageService->translate('aspect_ratio') ?>
        </h3>
        <div class="flex flex-col gap-3">
            <!-- Preset Aspect Ratios -->
            <div>
                <label for="image_aspect_ratio_preset" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('preset_aspect_ratio') ?></label>
                <select id="image_aspect_ratio_preset" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm text-sm py-2">
                    <option value="1:1">1:1 (<?= $languageService->translate('Square') ?>)</option>
                    <option value="4:3">4:3 (<?= $languageService->translate('Standard') ?>)</option>
                    <option value="3:2">3:2 (<?= $languageService->translate('Classic_Photo') ?>)</option>
                    <option value="16:9">16:9 (<?= $languageService->translate('Widescreen') ?>)</option>
                    <option value="custom"><?= $languageService->translate('custom_aspect_ratio') ?></option>
                </select>
            </div>

            <!-- Custom Aspect Ratio Inputs (initially hidden) -->
            <div id="custom_aspect_ratio_inputs" class="grid grid-cols-2 gap-4 mt-2 hidden">
                <div>
                    <label for="custom_ratio_x" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('ratio_width') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="custom_ratio_x_slider" min="1" max="100" step="1" value="16" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm">
                        <input type="number" id="custom_ratio_x" min="1" value="16" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>
                <div>
                    <label for="custom_ratio_y" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('ratio_height') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="custom_ratio_y_slider" min="1" max="100" step="1" value="9" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm">
                        <input type="number" id="custom_ratio_y" min="1" value="9" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>
            </div>

            <!-- Apply Button -->
             <div>
                <?= AdminInput::renderCta('apply_AR', 'apply_aspect_ratio_btn') ?>
            </div>
        </div>
    </div>

    <!-- Apply Frame Section -->
    <div id="image_frame_settings" class="mt-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
            <i class="material-icons text-brand-1">photo_frame</i> <?= $languageService->translate('apply_Frame') ?>
        </h3>
        <div class="mt-4 flex items-center justify-start">
            <?=
                AdminInput::renderCheckbox(
                    [
                        'name' => 'picture_show_frame_current',
                        'value' => 'false',
                        'attributes' => ['data-trigger' => 'current_image_element', 'id' => 'picture_show_frame_current', 'data-setting-prop' => 'show_frame']
                    ],
                    'collage-designer:show_single_frame' // language key
                )
?>
        </div>
    </div>
</div>