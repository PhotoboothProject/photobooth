<?php
// admin/collage-designer/components/design-selector.php

use Photobooth\Utility\AdminInput;
use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();

// --- PLACEHOLDER: Fetching data for the Collage Designer ---
// This will be filled with actual logic later.
// For now, we simulate empty values or a few mock options.
$currentDesign = '';
$optionsHtml = '
    <option value="">
        ' . $languageService->translate('collage_choose_new_design') . '
    </option>
    <option value="mock_design_1">Mock Design 1</option>
    <option value="mock_design_2" selected>Mock Design 2</option> <!-- Example: one option pre-selected -->
';

// --- Preparing the $configManagerSetting array (structure only, with real values later) ---
$configManagerSetting = [
    'name_input_id' => 'collage-designer-name',
    'name_input_placeholder' => 'collage_name_placeholder', // Language key for placeholder
    'select_id' => 'collage-select',
    'select_label_headline' => $languageService->translate('manage_collage_designs'), // Headline for this section
    'select_options_html' => $optionsHtml,
    'current_name_hidden_field_name' => 'collage[current_design]', // Name of the hidden field
    'current_name_hidden_field_value' => $currentDesign,

    'save_btn_id' => 'collage-save-btn',
    'save_btn_title_label_key' => 'collage_save', // Language key for the title
    'save_btn_onclick' => 'adminCollageSave();', // JavaScript function for saving (to be implemented later)
    'save_btn_icon_class' => 'fa fa-save',

    'load_btn_id' => 'collage-load-btn',
    'load_btn_title_label_key' => 'collage_load',
    'load_btn_onclick' => 'adminCollageLoad();', // JavaScript function for loading (to be implemented later)
    'load_btn_icon_class' => 'fa fa-download',

    'delete_btn_id' => 'collage-delete-btn',
    'delete_btn_title_label_key' => 'collage_delete',
    'delete_btn_onclick' => 'adminCollageDelete();', // JavaScript function for deleting (to be implemented later)
    'delete_btn_icon_class' => 'fa fa-trash',
];
?>

<div class="design_management flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <!-- The entire UI for design management is rendered here by renderConfigManager -->
    <?= AdminInput::renderConfigManager($configManagerSetting) ?>
</div>

<!-- Additional HTML for other parts of the designer could follow here -->
<!-- e.g., the actual drawing area, tools, etc. -->
