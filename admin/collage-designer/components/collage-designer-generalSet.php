<?php
// admin/collage-designer/components/collage-designer-generalSet.php
// -> general-settings-panel

use Photobooth\Utility\AdminInput;
use Photobooth\Utility\PathUtility;

?>

<div class="general_settings flex flex-col gap-2">
    <span class="w-full flex flex-col text-xl font-bold text-brand-1">
        <?= $languageService->translate('general') ?>
    </span>
    <div class="grid gap-2 mb-2 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
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
                'collage-designer:final_width'
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
                'collage-designer:final_height'
            )
?>
        </div>
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
        <div class="flex flex-col">
            <?=
            AdminInput::renderCheckbox(
                [
                    'name' => 'show-frame',
                    'value' => 'false',
                    'attributes' => ['data-trigger' => 'general', 'id' => 'show_frame']
                ],
                'collage-designer:show_frame'
            )
?>
        </div>
    </div>
    <div class="grid gap-2 mb-2 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
        <div class="col-span-2 flex flex-col">
            <?=
            AdminInput::renderImageSelect(
                [
                    'name' => 'background_image',
                    'value' => '',
                    'paths' => [
                        PathUtility::getAbsolutePath('resources/img/background'),
                        PathUtility::getAbsolutePath('private/images/background'),
                    ],
                    'attributes' => ['data-trigger' => 'general', 'id' => 'background_image']
                ],
                'collage:collage_background'
            )
?>
        </div>
        <div class="col-span-2 flex flex-col">
            <?=
            AdminInput::renderImageSelect(
                [
                    'name' => 'frame_image',
                    'value' => '',
                    'paths' => [
                        PathUtility::getAbsolutePath('resources/img/frames'),
                        PathUtility::getAbsolutePath('private/images/frames'),
                    ],
                    'attributes' => ['data-trigger' => 'general', 'id' => 'frame_image']
                ],
                'collage:collage_frame'
            )
?>
        </div>
    </div>
</div>
