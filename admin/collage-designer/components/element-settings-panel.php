<?php
// admin/collage-designer/components/element-settings-panel.php

use Photobooth\Utility\AdminInput;

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
</div>
