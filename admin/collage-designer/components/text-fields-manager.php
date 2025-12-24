<?php
// admin/collage-designer/components/text-fields-manager.php

use Photobooth\Utility\AdminInput;

?>

<div class="text_fields_manager flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
        Manage Text Fields
    </span>
    <div id="text_fields_list" class="flex flex-col gap-2 max-h-48 overflow-y-auto">
        <!-- Text fields will be dynamically added here by JavaScript -->
        <p class="text-gray-500" id="no_text_fields_message">No text fields added yet.</p>
    </div>
    <div>
        <?= AdminInput::renderCta('Add Text Field', 'addTextField()') ?>
    </div>
</div>
