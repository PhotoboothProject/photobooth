<?php
// admin/collage-designer/components/element-settings-panel.php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

// Placeholder for actual font options, adjust as needed based on your PHP logic
$font_family_options = [
    ['value' => 'Arial', 'label' => 'Arial'],
    ['value' => 'Verdana', 'label' => 'Verdana'],
    ['value' => 'Times New Roman', 'label' => 'Times New Roman'],
    // ... more fonts
];

?>

<div id="element_settings_panel" class="flex flex-col gap-4 p-4 rounded-md bg-white w-full">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1">
        <span class="flex items-baseline gap-1">
            <?= $languageService->translate('element_settings_title') ?> (<span id="selected_element_type_display" class="font-normal"></span>)
        </span>
        <span id="selected_element_id_display" class="text-sm font-normal text-gray-500"></span>
    </span>

    <!-- Common Element Settings (Position, Size, Rotation) -->
    <div class="flex flex-col gap-6">
        <!-- Position Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="material-icons text-brand-1">open_with</i> <?= $languageService->translate('position') ?>
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="element_x_position" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('x_position') ?> (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="element_x_position_slider" min="0" max="100" step="0.1" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="x">
                        <input type="number" id="element_x_position" min="0" max="100" step="0.1" value="0" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm" data-setting-prop="x">
                    </div>
                </div>
                <div>
                    <label for="element_y_position" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('y_position') ?> (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="element_y_position_slider" min="0" max="100" step="0.1" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="y">
                        <input type="number" id="element_y_position" min="0" max="100" step="0.1" value="0" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm" data-setting-prop="y">
                    </div>
                </div>
            </div>
        </div>

        <!-- Size Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="material-icons text-brand-1">zoom_out_map</i> <?= $languageService->translate('size') ?>
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="element_width" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('width') ?> (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="element_width_slider" min="0.1" max="100" step="0.1" value="50" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="width">
                        <input type="number" id="element_width" min="0" max="100" step="0.1" value="50" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm" data-setting-prop="width">
                    </div>
                </div>
                <div>
                    <label for="element_height" class="block text-sm font-medium text-gray-700 mb-1"><?= $languageService->translate('height') ?> (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="element_height_slider" min="0.1" max="100" step="0.1" value="50" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="height">
                        <input type="number" id="element_height" min="0" max="100" step="0.1" value="50" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm" data-setting-prop="height">
                    </div>
                </div>
            </div>
             <!-- Lock Aspect Ratio (future feature) -->
            <div class="mt-4 flex items-center justify-start">
                <?=
                    AdminInput::renderCheckbox(
                        [
                            'name' => 'lock_aspect_ratio',
                            'value' => 'false',
                            'attributes' => ['id' => 'lock_aspect_ratio']
                        ],
                        'collage-designer:lock_aspect_ratio' // language key
                    )
?>
            </div>
        </div>

        <!-- Rotation Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="material-icons text-brand-1">rotate_right</i> <?= $languageService->translate('rotation') ?>
            </h3>
            <div class="flex items-center gap-2">
                <input type="range" id="element_rotation_slider" min="-180" max="180" step="1" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="rotation">
                <input type="number" id="element_rotation" min="-180" max="180" step="1" value="0" class="w-20 p-1 text-center rounded-md border-gray-300 shadow-sm text-sm" data-setting-prop="rotation">
            </div>
        </div>
    </div>

    <!-- Specific Settings for Text (Hidden for now) -->
    <div id="text_specific_settings" class="hidden border-t border-gray-200 pt-4 mt-4">
        <span class="w-full flex flex-col text-lg font-bold text-brand-1 mb-2">
            <?= $languageService->translate('text_settings') ?>
        </span>
        <textarea id="text_content" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" placeholder="<?= $languageService->translate('your_text_content') ?>" data-setting-prop="content"></textarea>
        <div class="grid gap-2 mb-4 grid-cols-2 mt-2">
            <div>
                <?=
    AdminInput::renderFontSelect(
        [
            'name' => 'text_font_family_current',
            'value' => '',
            'paths' => [
                PathUtility::getAbsolutePath('resources/fonts'),
                PathUtility::getAbsolutePath('private/fonts'),
            ],
            'attributes' => ['data-trigger' => 'current_text_element', 'id' => 'text_font_family_current', 'data-setting-prop' => 'font_family']
        ],
        'collage:textoncollage_font', // Keep original language key
        $font_family_options // Pass available font options
    )
?>
            </div>
            <div>
                <?=
    AdminInput::renderColor(
        [
            'name' => 'text_font_color_current',
            'value' => '#000000',
            'placeholder' => 'text font color',
            'attributes' => ['data-trigger' => 'current_text_element', 'id' => 'text_font_color_current', 'data-setting-prop' => 'font_color']
        ],
        'collage:textoncollage_font_color' // Keep original language key
    )
?>
            </div>
            <div>
                <label for="text_font_size_current" class="block text-sm font-medium text-gray-700"><?= $languageService->translate('font_size') ?> (%)</label>
                <input type="range" id="text_font_size_current_slider" min="0.1" max="10" step="0.1" value="2" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm" data-setting-prop="font_size">
                <input type="number" id="text_font_size_current" value="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="<?= $languageService->translate('font_size_in_percent') ?>" data-setting-prop="font_size">
            </div>
            <!-- Additional text-specific settings can go here -->
        </div>
    </div>

    <!-- Specific Settings for Images (Hidden for now, unless you add specific image-cropping controls here later) -->
    <div id="image_specific_settings" class="hidden border-t border-gray-200 pt-4 mt-4">
        <span class="w-full flex flex-col text-lg font-bold text-brand-1 mb-2">
            <?= $languageService->translate('image_settings') ?>
        </span>
        <div class="grid gap-2 mb-4 grid-cols-2">
            <div>
                <?=
    AdminInput::renderCheckbox(
        [
            'name' => 'picture_show_frame_current',
            'value' => 'false',
            'attributes' => ['data-trigger' => 'current_image_element', 'id' => 'picture_show_frame_current', 'data-setting-prop' => 'show_frame']
        ],
        'collage:generator:show_single_frame' // Keep original language key
    )
?>
            </div>
            <!-- Additional image-specific settings can go here -->
        </div>
    </div>
    
    <button id="panelDeleteElementBtn" class="w-fit self-end btn btn-sm bg-red-500 text-white p-2 rounded-md hover:bg-red-600 transition flex items-center gap-2">
        <i class="material-icons">delete</i> <?= $languageService->translate('delete') ?>
    </button>
</div>
