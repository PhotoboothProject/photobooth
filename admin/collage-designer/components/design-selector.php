<?php

use Photobooth\Utility\AdminInput;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\CollageLayoutScanner;

$languageService = LanguageService::getInstance();

$currentDesign = '';

$designes = CollageLayoutScanner::scanLayouts();

$optionsHtml = '
            <option value="">
                ' . $languageService->translate('collage_choose_new_design') . '
            </option>';

foreach ($designes as $mainGroupTitle => $subGroups) { // Iterate over main groups (e.g., "Standard Layouts", "Custom Layouts")
    $optionsHtml .= '<optgroup label="' . htmlspecialchars($mainGroupTitle, ENT_QUOTES) . '">';
    
    // Sort subgroups by their translated titles to ensure consistent order
    // This is a simple key sort (by the translated name)
    ksort($subGroups);

    foreach ($subGroups as $subGroupTitle => $layouts) { // Iterate over subgroups (e.g., "Portrait Layouts", "Community Layouts")
        // Add a disabled option as a heading for the subgroup
        if (!empty($subGroupTitle)) {
            $optionsHtml .= '<option disabled>' . str_repeat('&nbsp;', 4) . '--- ' . htmlspecialchars($subGroupTitle, ENT_QUOTES) . ' ---</option>';
        }

        // Sort the layouts within the subgroup by their name
        uasort($layouts, function($a, $b) {
            return strcmp($a['name'] ?? $a['id'], $b['name'] ?? $b['id']);
        });

        foreach ($layouts as $layoutId => $layoutData) { // Now these are the actual layout data
            $selected = ($layoutId === $currentDesign) ? ' selected="selected"' : '';
            
            // Use the null coalescing operator for "name" to avoid Deprecated warnings
            // and use "id" as a fallback if "name" should be missing (which the scanner should already handle)
            $displayName = htmlspecialchars($layoutData['name'] ?? $layoutData['id'] ?? '', ENT_QUOTES);

            $optionsHtml .= '<option value="' . htmlspecialchars($layoutId, ENT_QUOTES) . '"' . $selected . '>';
            $optionsHtml .= str_repeat('&nbsp;', 8); // Additional indentation for layout elements
            $optionsHtml .= $displayName . '</option>';
        }
    }
    $optionsHtml .= '</optgroup>';
}

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

    'load_btn_id' => 'collage-load-btn',
    'load_btn_title_label_key' => 'collage_load',
    'load_btn_onclick' => 'adminCollageLoad();', // JavaScript function for loading (to be implemented later)

    'delete_btn_id' => 'collage-delete-btn',
    'delete_btn_title_label_key' => 'collage_delete',
    'delete_btn_onclick' => 'adminCollageDelete();', // JavaScript function for deleting (to be implemented later)
];
?>

<div class="design_management flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <!-- The entire UI for design management is rendered here by renderConfigManager -->
    <?= AdminInput::renderConfigManager($configManagerSetting) ?>
</div>

<!-- Additional HTML for other parts of the designer could follow here -->
<!-- e.g., the actual drawing area, tools, etc. -->
