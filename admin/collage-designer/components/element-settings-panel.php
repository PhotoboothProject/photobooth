<?php
// admin/collage-designer/components/element-settings-panel.php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

?>

<div id="element_settings_panel" class="flex flex-col gap-4 p-4 border border-gray-200 rounded-md hidden">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
        Element Settings (<span id="selected_element_type_display"></span>)
        <span id="selected_element_id_display" class="text-sm font-normal text-gray-500"></span>
    </span>

    <!-- Common Element Settings (Position, Size, Rotation) -->
    <div class="grid gap-2 mb-4 grid-cols-2">
        <div>
            <label for="element_x_position" class="block text-sm font-medium text-gray-700">X-Position (%)</label>
            <input type="range" id="element_x_position_slider" min="0" max="100" step="0.1" value="0" class="w-full" data-setting-prop="x">
            <input type="number" id="element_x_position" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-setting-prop="x">
        </div>
        <div>
            <label for="element_y_position" class="block text-sm font-medium text-gray-700">Y-Position (%)</label>
            <input type="range" id="element_y_position_slider" min="0" max="100" step="0.1" value="0" class="w-full" data-setting-prop="y">
            <input type="number" id="element_y_position" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-setting-prop="y">
        </div>
        <div>
            <label for="element_width" class="block text-sm font-medium text-gray-700">Width (%)</label>
            <input type="range" id="element_width_slider" min="0" max="100" step="0.1" value="50" class="w-full" data-setting-prop="width">
            <input type="number" id="element_width" value="50" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-setting-prop="width">
        </div>
        <div>
            <label for="element_height" class="block text-sm font-medium text-gray-700">Height (%)</label>
            <input type="range" id="element_height_slider" min="0" max="100" step="0.1" value="50" class="w-full" data-setting-prop="height">
            <input type="number" id="element_height" value="50" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-setting-prop="height">
        </div>
        <div class="col-span-2">
            <label for="element_rotation" class="block text-sm font-medium text-gray-700">Rotation (degrees)</label>
            <input type="range" id="element_rotation_slider" min="-180" max="180" step="1" value="0" class="w-full" data-setting-prop="rotation">
            <input type="number" id="element_rotation" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-setting-prop="rotation">
        </div>
    </div>

    <!-- Specific Settings for Text -->
    <div id="text_specific_settings" class="hidden">
        <span class="w-full flex flex-col text-lg font-bold text-brand-1 mb-2">
            Text Settings
        </span>
        <textarea id="text_content" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" placeholder="Your text content" data-setting-prop="content"></textarea>
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
                <label for="text_font_size_current" class="block text-sm font-medium text-gray-700">Font Size (%)</label>
                <input type="range" id="text_font_size_current_slider" min="0.1" max="10" step="0.1" value="2" class="w-full" data-setting-prop="font_size">
                <input type="number" id="text_font_size_current" value="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Font size in %" data-setting-prop="font_size">
            </div>
            <!-- Additional text-specific settings can go here -->
        </div>
    </div>

    <!-- Specific Settings for Images -->
    <div id="image_specific_settings" class="hidden">
        <span class="w-full flex flex-col text-lg font-bold text-brand-1 mb-2">
            Image Settings
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
    <button onclick="deleteSelectedElement()" class="w-fit self-end bg-red-500 text-white p-2 rounded-md hover:bg-red-600 transition">
        <i class="fa fa-trash"></i> Delete Element
    </button>
</div>
