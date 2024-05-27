<?php
require_once '../../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\AdminInput;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

$languageService = LanguageService::getInstance();
$pageTitle = 'Collage generator - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$collageConfigFilePath = PathUtility::getAbsolutePath('private/' . $config['collage']['layout']);
$collageJson = json_decode((string)file_get_contents($collageConfigFilePath), true);

?>

<div class="w-full h-screen bg-brand-2 px-3 md:px-6 py-6 md:py-12 overflow-x-hidden overflow-y-auto">
	<style>
		@font-face {
  font-family: "GreatVibes-Regular";
  src: url(/resources/fonts/GreatVibes-Regular.ttf) format("truetype");
}
div.provafont { font-family: "GreatVibes-Regular", sans-serif }
	</style>
    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-[1500px] rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl place-items-center">
            <div class="w-full text-center flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
                Collage Layout Generator
            </div>
			<div class="provafont">
				Photobooth we love OpenSource
			</div>
            <div class="result_section mt-4 w-full flex gap-4 flex-col md:flex-row">
                <div class="result_positions md:max-h-[75vh] p-2 md:p-4 overflow-y-auto flex-1">
                    <div class="general_settings">
						<input id="current_config" type="hidden" value='<?= json_encode($collageJson) ?>' />
						<div class="w-full flex flex-col gap-2 my-4 md:my-8">
							<div>
								<?= AdminInput::renderCta('load_current_configuration', 'loadCurrentConfiguration') ?>
							</div>
							<div>
								<?=
                                    AdminInput::renderColor(
                                        [
                                            'name' => 'background_color',
                                            'value' => '#FFFFFF',
                                            'placeholder' => 'background color',
                                            'attributes' => ['data-trigger' => 'general']
                                        ],
                                        $languageService->translate('background_color')
                                    )
?>
							</div>
							<div>
								<?=
    AdminInput::renderImageSelect(
        [
            'name' => 'generator-background',
            'value' => '',
            'paths' => [
                PathUtility::getAbsolutePath('resources/img/background'),
                PathUtility::getAbsolutePath('private/images/backgrounds'),
            ],
            'attributes' => ['data-trigger' => 'general']
        ],
        $languageService->translate('choose_background')
    )
?>
							</div>
							<div>
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
        $languageService->translate('choose_frame')
    )
?>
							</div>
						</div>
                        <div>
                            <span class="w-full flex flex-col items-center justify-center text-2md font-bold text-brand-1 mb-2">General Settings</span>
                        </div>
                        <div class="grid gap-2">
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
                                <div>
									<?=
        AdminInput::renderCheckbox(
            [
                'name' => 'portrait',
                'value' => 'false',
                'attributes' => ['data-trigger' => 'general']
            ],
            $languageService->translate('portrait')
        )
?>
                                </div>
                                <div>
									<?=
    AdminInput::renderCheckbox(
        [
            'name' => 'rotate_after_creation',
            'value' => 'false',
            'attributes' => ['data-trigger' => 'general']
        ],
        $languageService->translate('rotate_after_creation')
    )
?>
                                </div>
                            </div>
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
                                <div>
									<?=
    AdminInput::renderInput(
        [
            'type' => 'number',
            'name' => 'final_width',
            'value' => '1500',
            'placeholder' => 'collage width',
            'attributes' => ['data-trigger' => 'general']
        ],
        $languageService->translate('final_width')
    )
?>
                                </div>
								<div>
									<?=
    AdminInput::renderInput(
        [
            'type' => 'number',
            'name' => 'final_height',
            'value' => '1000',
            'placeholder' => 'collage height',
            'attributes' => ['data-trigger' => 'general']
        ],
        $languageService->translate('final_height')
    )
?>
								</div>
                            </div>
                            <div>
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
        'value' => 'always',
        'attributes' => ['data-trigger' => 'general']
    ],
    $languageService->translate('apply_frame')
)
?>
                            </div>
                            <div class="grid gap-2 grid-cols-[repeat(auto-fit,_minmax(150px,_1fr))]">
                                <div>
									<?=
        AdminInput::renderCheckbox(
            [
                'name' => 'show-background',
                'value' => 'false',
                'attributes' => ['data-trigger' => 'general']
            ],
            $languageService->translate('show-background')
        )
?>
                                </div>
                                <div>
									<?=
    AdminInput::renderCheckbox(
        [
            'name' => 'show-frame',
            'value' => 'true',
            'attributes' => ['data-trigger' => 'general']
        ],
        $languageService->translate('show-frame')
    )
?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="images_settings flex flex-col gap-4">
                        <div id="layout_containers" class="flex gap-4 overflow-x-auto">
                            <?= generateImageLayoutInputs($languageService) ?>
                        </div>
                        <div>
							<?= AdminInput::renderCta('add_image', 'addImage') ?>
                        </div>
                    </div>
                </div>
                <div class="result_images md:max-h-[75vh] flex-1 relative lg:flex-[3_1_0%] p-4 md:p-8 bg-slate-300">
					<div id="result_canvas" class="relative m-0 left-[50%] top-[50%] right-0 bottom-0 translate-y-[0%] md:translate-y-[-50%] translate-x-[-50%] max-w-full max-h-full shadow-xl">
						<div id="collage_background" class="absolute h-full">
							<img class="h-full hidden" src="" alt="Choose the background">
						</div>
						<div id="picture-0" class="absolute overflow-hidden w-full h-full">
							<img class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/seal-station-norddeich-01.jpg">
							<img class="picture-frame absolute object-cover object-left-top rotate-0 hidden" />
						</div>
						<div id="picture-1" class="absolute overflow-hidden w-full h-full hidden">
							<img class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/seal-station-norddeich-02.jpg">
							<img class="picture-frame absolute object-cover object-left-top rotate-0 hidden" />
						</div>
						<div id="picture-2" class="absolute overflow-hidden w-full h-full hidden">
							<img class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/seal-station-norddeich-03.jpg">
							<img class="picture-frame absolute object-cover object-left-top rotate-0 hidden" />
						</div>
						<div id="picture-3" class="absolute overflow-hidden w-full h-full hidden">
							<img class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/seal-station-norddeich-04.jpg">
							<img class="picture-frame absolute object-cover object-left-top rotate-0 hidden" />
						</div>
						<div id="picture-4" class="absolute overflow-hidden w-full h-full hidden">
							<img class="absolute object-cover object-left-top rotate-0" src="/resources/img/demo/seal-station-norddeich-05.jpg">
							<img class="picture-frame absolute object-cover object-left-top rotate-0 hidden" />
						</div>
						<div id="collage_frame" class="absolute h-full">
							<img class="h-full hidden" src="" alt="Choose the frame">
						</div>
					</div>
                </div>
            </div>
        </div>

        <div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20">
        </div>

		<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
			<?php
                echo getMenuBtn(PathUtility::getPublicPath('admin'), 'admin_panel', $config['icons']['admin']);

if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    echo getMenuBtn(PathUtility::getPublicPath('login/logout.php'), 'logout', $config['icons']['logout']);
}
?>
			</div>
		</div>
    </div>
</div>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');

function generateImageLayoutInputs($languageService)
{
    $max_images = 5;
    $dom = '';

    for ($i = 0; $i < $max_images; $i++) {
        $hidden_class = 'hidden';
        if ($i == 0) {
            $hidden_class = '';
        }

        $dom .= '<div data-picture="picture-' . $i . '" style="background-image: linear-gradient(rgba(255,255,255,.5), rgba(255,255,255,.5)), url(\'/resources/img/demo/seal-station-norddeich-0' . ($i + 1) . '.jpg\')" class="relative w-full p-3 md:p-5 grid grid-cols-[repeat(auto-fit,_minmax(100px,_1fr))] gap-2 bg-cover bg-center ' . $hidden_class . '">';
        $dom .= '
			<div class="absolute top-1 right-1 z-10 hidden">
				<button class="bg-white p-1 rounded-md" onclick="hideImage(\'picture-' . $i . '\')"><i class="fa fa-minus fa-lg"></i></button>
			</div>
		';
        $dom .= '<div>';
        $dom .= AdminInput::renderInput(
            [
                'type' => 'text',
                'name' => 'picture-x-position-' . $i,
                'value' => '0',
                'placeholder' => 'x position',
                'attributes' => ['data-prop' => 'left', 'data-trigger' => 'image']
            ],
            $languageService->translate('x_position')
        );
        $dom .= '</div><div>';
        $dom .= AdminInput::renderInput(
            [
                'type' => 'text',
                'name' => 'picture-y-position-' . $i,
                'value' => '0',
                'placeholder' => 'y position',
                'attributes' => ['data-prop' => 'top', 'data-trigger' => 'image']
            ],
            $languageService->translate('y_position')
        );
        $dom .= '</div><div>';
        $dom .= AdminInput::renderInput(
            [
                'type' => 'text',
                'name' => 'picture-width-' . $i,
                'value' => '',
                'placeholder' => $languageService->translate('image_width'),
                'attributes' => ['data-prop' => 'width', 'data-trigger' => 'image']
            ],
            $languageService->translate('image_width')
        );
        $dom .= '</div><div>';
        $dom .= AdminInput::renderInput(
            [
                'type' => 'text',
                'name' => 'picture-height-' . $i,
                'value' => '',
                'placeholder' => $languageService->translate('image_height'),
                'attributes' => ['data-prop' => 'height', 'data-trigger' => 'image']
            ],
            $languageService->translate('image_height')
        );
        $dom .= '</div><div class="col-span-2">';
        $dom .= AdminInput::renderRange(
            [
                'type' => 'number',
                'name' => 'picture-rotation-' . $i,
                'value' => '0',
                'unit' => 'degrees',
                'range_min' => '-90',
                'range_max' => '90',
                'range_step' => '1',
                'placeholder' => 'degrees',
                'attributes' => ['data-prop' => 'transform', 'data-trigger' => 'image']
            ],
            $languageService->translate('image_rotation')
        );
        $dom .= '</div></div>';
    }

    return $dom;
}
?>
