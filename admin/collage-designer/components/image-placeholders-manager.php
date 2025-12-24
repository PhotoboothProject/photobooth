<?php
// admin/collage-designer/components/image-placeholders-manager.php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

?>

<div class="image_placeholders_manager flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
        Manage Image Placeholders
    </span>
    <div id="image_placeholders_list" class="flex gap-4 flex-wrap max-h-48 overflow-y-auto">
        <?php for ($i = 0; $i < count($demoImages); $i++) {
            $imagePath = PathUtility::getPublicPath($demoImages[$i]);
            // Initial visibility logic for image placeholders.
            // In a multi-design scenario, this will be handled by JS loading the design.
            $initialDisplay = ($i < $config['collage']['limit']) ? 'block' : 'hidden'; // Example based on old config
            ?>
            <button type="button" data-element-id="picture-<?=$i?>" data-element-type="image" class="image_placeholder_item bg-gray-100 p-2 rounded-md flex flex-col items-center border border-transparent hover:border-blue-500 w-24 h-24 justify-center <?=$initialDisplay?>" title="Image Placeholder <?=$i + 1?>">
                <img src="<?=$imagePath?>" class="w-16 h-16 object-cover rounded-sm mb-1 pointer-events-none" alt="Image <?=$i + 1?>">
                <span class="text-xs">Image <?=$i + 1?></span>
            </button>
        <?php } ?>
    </div>
    <div>
        <!-- This button will add a new image placeholder to the collage -->
        <?= AdminInput::renderCta('Add Image Placeholder', 'addImagePlaceholder()') ?>
    </div>
</div>
