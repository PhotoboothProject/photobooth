<?php
// admin/collage-designer/components/placeholder-settings.php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;
?>

<div class="placeholder_settings flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
        <?= $languageService->translate('collage:generator:placeholder_settings') ?>
    </span>
    <div class="grid gap-2 mb-4 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderCheckbox(
                    [
                        'name' => 'enable_placeholder_image',
                        'value' => 'false',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:collage_placeholder'
                )
            ?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderInput(
                    [
                        'type' => 'number',
                        'name' => 'placeholder_image_position',
                        'value' => '1',
                        'placeholder' => 'placehoder image position',
                        'attributes' => [
                            'min' => '1',
                            'max' => '8', // This max will need to be dynamic based on the number of actual image placeholders
                            'data-trigger' => 'general'
                        ]
                    ],
                    'collage:collage_placeholderposition'
                )
            ?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderImageSelect(
                    [
                        'name' => 'placeholder_image',
                        'value' => '',
                        'paths' => [
                            PathUtility::getAbsolutePath('resources/img/demo'),
                            PathUtility::getAbsolutePath('private/images/placeholder'),
                        ],
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'choose_placeholder'
                )
            ?>
        </div>
    </div>
</div>
