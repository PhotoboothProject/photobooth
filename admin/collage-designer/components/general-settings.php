<?php
// admin/collage-designer/components/general-settings.php

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;
?>

<div class="general_settings flex flex-col gap-4 p-4 border border-gray-200 rounded-md">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
        <?= $languageService->translate('general') ?>
    </span>
    <div class="grid gap-2 mb-4 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderColor(
                    [
                        'name' => 'background_color',
                        'value' => '#FFFFFF',
                        'placeholder' => 'background color',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:collage_background_color'
                )
            ?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?= AdminInput::renderImageSelect(
                [
                    'name' => 'generator-background',
                    'value' => '',
                    'paths' => [
                        PathUtility::getAbsolutePath('resources/img/background'),
                        PathUtility::getAbsolutePath('private/images/background'),
                    ],
                    'attributes' => ['data-trigger' => 'general']
                ],
                'collage:collage_background'
            )
            ?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderImageSelect(
                    [
                        'name' => 'generator-frame',
                        'value' => '',
                        'paths' => [
                            PathUtility::getAbsolutePath('resources/img/frames'),
                            PathUtility::getAbsolutePath('private/images/frames'),
                        ],
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:collage_frame'
                )
            ?>
        </div>
        <div class="flex flex-col">
            <?=
                AdminInput::renderInput(
                    [
                        'type' => 'number',
                        'name' => 'final_width',
                        'value' => '1500',
                        'placeholder' => 'collage width',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:generator:final_width'
                )
            ?>
        </div>
        <div class="flex flex-col">
            <?=
                AdminInput::renderInput(
                    [
                        'type' => 'number',
                        'name' => 'final_height',
                        'value' => '1000',
                        'placeholder' => 'collage height',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:generator:final_height'
                )
            ?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?=
                AdminInput::renderSelect(
                    [
                        'type' => 'select',
                        'name' => 'apply_frame',
                        'options' => [
                            'off' => 'Off',
                            'always' => 'Always',
                            'once' => 'Once',
                        ],
                        'value' => 'once',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:collage_take_frame'
                )
            ?>
        </div>
        <div class="flex flex-col">
            <?=
                AdminInput::renderCheckbox(
                    [
                        'name' => 'show-background',
                        'value' => 'false',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:generator:show_background'
                )
            ?>
        </div>
        <div class="flex flex-col">
            <?=
                AdminInput::renderCheckbox(
                    [
                        'name' => 'show-frame',
                        'value' => 'false',
                        'attributes' => ['data-trigger' => 'general']
                    ],
                    'collage:generator:show_frame'
                )
            ?>
        </div>
    </div>
</div>
